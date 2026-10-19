<?php

namespace App\Services;

use App\Enums\AssetStatus;
use App\Enums\LocationType;
use App\Imports\AssetImport;
use App\Imports\ColumnExampleImport;
use App\Models\Asset;
use App\Models\AssetCustomFieldValue;
use App\Models\AssetList;
use App\Models\AssetListItem;
use App\Models\CustomFieldDefinition;
use App\Models\CustomFieldSet;
use App\Models\ItemType;
use App\Models\ItemTypeAlias;
use App\Models\Location;
use App\Models\LocationAlias;
use App\Support\PerformanceLimits;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Exceptions\NoSheetsFoundException;
use Maatwebsite\Excel\Exceptions\NoTypeDetectedException;
use Maatwebsite\Excel\Exceptions\UnreadableFileException;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\Reader\Exception as SpreadsheetReaderException;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

class ImportService
{
    public const CREATE_NEW_FIELD = 'create_new_field';

    public const CUSTOM_FIELD_PREFIX = 'custom_field:';

    /** @var Collection<int, CustomFieldDefinition>|null */
    protected ?Collection $importableCustomFieldDefinitionsCache = null;

    /** @var array<string, ItemType> */
    protected array $itemTypeResolutionCache = [];

    /** @var array<string, Location> */
    protected array $locationResolutionCache = [];

    /** @var array<string, int|null> */
    protected array $locationPathResolutionCache = [];

    protected ?ManufacturerCatalogueService $manufacturerCatalogue = null;

    /** @var list<string> */
    public const DUPLICATE_KEYS = [
        'serial_number',
        'fmi_ast',
        'mac_address',
        'rig_tag',
        'tp_barcode',
        'ip_address',
    ];

    /** @var list<string> */
    public const DATE_FIELDS = [
        'purchase_date',
        'warranty_expiry',
        'test_tag_expiry',
    ];

    /** Max duplicate rows rendered on the resolve screen (bulk action still applies to all). */
    public const DUPLICATES_PREVIEW_LIMIT = 100;

    /** @var list<string> */
    public const META_FIELDS = [
        'item_type',
        'location_building',
        'location_level',
        'location_room',
        'location_rack',
    ];

    /**
     * Max lengths for string columns written during import (matches schema / Asset form rules).
     *
     * @var array<string, int>
     */
    public const ASSET_STRING_MAX_LENGTHS = [
        'name' => 255,
        'status' => 32,
        'serial_number' => 255,
        'supplier' => 255,
        'fmi_ast' => 255,
        'tp_barcode' => 255,
        'rig_tag' => 255,
        'ip_address' => 45,
        'mac_address' => 32,
        'manufacturer' => 255,
        'model' => 255,
    ];

    /** MySQL TEXT / custom-field value ceiling used when sanitising long import cells. */
    public const ASSET_TEXT_MAX_LENGTH = 65535;

    /** Item types, locations, and related free-text names. */
    public const META_NAME_MAX_LENGTH = 255;

    /** @var array<string, string> */
    public const ASSET_FIELDS = [
        'name' => 'Name',
        'description' => 'Description',
        'item_type' => 'Item Type',
        'location_building' => 'Location: Building',
        'location_level' => 'Location: Level',
        'location_room' => 'Location: Room',
        'location_rack' => 'Location: Rack',
        'status' => 'Status',
        'manufacturer' => 'Manufacturer',
        'model' => 'Model',
        'serial_number' => 'Serial Number',
        'purchase_date' => 'Purchase Date',
        'warranty_expiry' => 'Warranty Expiry',
        'supplier' => 'Supplier',
        'replacement_cost' => 'Replacement Cost',
        'fmi_ast' => 'FMI AST#',
        'tp_barcode' => 'TP Barcode',
        'rig_tag' => 'RIG Tag #',
        'ip_address' => 'IP Address',
        'mac_address' => 'MAC Address',
        'test_tag_expiry' => 'Test & Tag Expiry',
        'quantity' => 'Quantity',
        'notes' => 'Notes',
    ];

    /**
     * Read the heading row from an uploaded spreadsheet.
     *
     * @return list<string>
     */
    public function readHeadingColumns(UploadedFile|string $file): array
    {
        try {
            $sheets = Excel::toArray(new HeadingRowImport, $file);
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'file' => $this->userFacingReadError($e),
            ]);
        }

        // Use the first sheet only; do not reject multi-sheet workbooks.
        $columns = $sheets[0][0] ?? [];
        if (! is_array($columns)) {
            $columns = [];
        }

        return array_values(array_map(
            fn ($column) => $column === null ? '' : (string) $column,
            $columns,
        ));
    }

    /**
     * Find a sample cell value for each heading, scanning data rows until one is found.
     *
     * @param  list<string>  $columns
     * @return array<string, string|null> column => example (null when the column has no content)
     */
    public function readColumnExamples(UploadedFile|string $file, array $columns): array
    {
        $examples = [];
        foreach ($columns as $column) {
            $examples[(string) $column] = null;
        }

        if ($examples === []) {
            return [];
        }

        try {
            $reader = new ColumnExampleImport;
            Excel::import($reader, $file);
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'file' => $this->userFacingReadError($e),
            ]);
        }

        foreach ($reader->rows as $row) {
            if (! $row instanceof Collection) {
                $row = collect($row);
            }

            $stillMissing = false;

            foreach ($examples as $column => $example) {
                if ($example !== null) {
                    continue;
                }

                $formatted = $this->formatExampleCell($this->cellValue($row, $column));
                if ($formatted !== null) {
                    $examples[$column] = $formatted;
                } else {
                    $stillMissing = true;
                }
            }

            if (! $stillMissing) {
                break;
            }
        }

        return $examples;
    }

    /**
     * Read spreadsheet data rows using the confirmed column mapping headings.
     *
     * @return Collection<int, Collection<string, mixed>>
     */
    public function readRows(string $absolutePath): Collection
    {
        try {
            // #region agent log
            $__dbgReadT0 = microtime(true);
            $__dbgLog = function (string $message, array $data = []) use ($__dbgReadT0): void {
                $payload = [
                    'sessionId' => 'd766ca',
                    'runId' => 'post-fix',
                    'hypothesisId' => 'A',
                    'location' => 'ImportService.php:readRows',
                    'message' => $message,
                    'data' => array_merge(['elapsed_ms' => (int) round((microtime(true) - $__dbgReadT0) * 1000)], $data),
                    'timestamp' => (int) round(microtime(true) * 1000),
                ];
                @file_put_contents(base_path('debug-d766ca.log'), json_encode($payload)."\n", FILE_APPEND);
            };
            $__dbgLog('excel_import_start', ['path_basename' => basename($absolutePath)]);
            // #endregion

            $reader = new AssetImport;
            Excel::import($reader, $absolutePath);

            // #region agent log
            $__dbgLog('excel_import_done', ['row_count' => $reader->rows->count()]);
            // #endregion

            $maxRows = PerformanceLimits::int('import_max_rows');
            if ($reader->rows->count() > $maxRows) {
                throw ValidationException::withMessages([
                    'file' => sprintf(
                        'This spreadsheet has %d data rows; the import limit is %d. Split the file or raise the limit (%s).',
                        $reader->rows->count(),
                        $maxRows,
                        PerformanceLimits::raiseHint('import_max_rows'),
                    ),
                ]);
            }

            return $reader->rows;
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'file' => $this->userFacingReadError($e),
            ]);
        }
    }

    /**
     * Persist prepared import rows outside the session/cache payload.
     *
     * @param  list<array<string, mixed>>  $prepared
     */
    public function storePreparedRows(array $prepared, ?int $userId = null): string
    {
        $token = Str::lower(Str::random(24));
        $path = 'imports/prepared/'.($userId ?: 'anon').'-'.$token.'.json';

        // Stream row-by-row JSON to avoid doubling peak memory with one giant encode.
        $absolute = Storage::path($path);
        $directory = dirname($absolute);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $handle = fopen($absolute, 'wb');
        if ($handle === false) {
            throw ValidationException::withMessages([
                'file' => 'Unable to store prepared import rows. Check storage permissions.',
            ]);
        }

        try {
            fwrite($handle, '[');
            $first = true;
            foreach ($prepared as $row) {
                if (! $first) {
                    fwrite($handle, ',');
                }
                $first = false;
                fwrite($handle, json_encode($row, JSON_THROW_ON_ERROR));
            }
            fwrite($handle, ']');
        } finally {
            fclose($handle);
        }

        return $path;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function loadPreparedRows(string $path): array
    {
        if (! Storage::exists($path)) {
            throw ValidationException::withMessages([
                'file' => 'Import session expired. Upload again.',
            ]);
        }

        $decoded = json_decode((string) Storage::get($path), true);

        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                'file' => 'Import session expired. Upload again.',
            ]);
        }

        /** @var list<array<string, mixed>> $decoded */
        return $decoded;
    }

    public function forgetPreparedRows(?string $path): void
    {
        if ($path && Storage::exists($path)) {
            Storage::delete($path);
        }
    }

    /**
     * Map spreadsheet library failures to a short explanation for the user.
     */
    public function userFacingReadError(Throwable $e): string
    {
        if ($e instanceof NoTypeDetectedException) {
            return 'That file type is not supported or could not be recognised. Upload a CSV or Excel file (.csv, .xlsx, or .xls).';
        }

        if ($e instanceof UnreadableFileException) {
            return 'Unable to read that file. It may be corrupted or password-protected.';
        }

        if ($e instanceof NoSheetsFoundException) {
            return 'Unable to find any worksheets in that file.';
        }

        $message = strtolower($e->getMessage());

        if (str_contains($message, 'password') || str_contains($message, 'encrypt')) {
            return 'Unable to read password-protected files. Remove the password and try again.';
        }

        if (
            $e instanceof SpreadsheetReaderException
            || $e instanceof SpreadsheetException
            || str_contains($message, 'zip')
            || str_contains($message, 'ole')
            || str_contains($message, 'workbook')
            || str_contains($message, 'worksheet')
        ) {
            return 'Unable to process that spreadsheet. Export as CSV or a simple Excel file and try again.';
        }

        return 'Unable to process that file. Upload a valid CSV or Excel file.';
    }

    /**
     * Core + custom field options for the map step dropdowns.
     *
     * @return list<array{value: string, label: string}>
     */
    public function fieldOptionsForMapping(): array
    {
        $options = [];

        foreach (self::ASSET_FIELDS as $field => $label) {
            $options[] = [
                'value' => $field,
                'label' => $label,
            ];
        }

        foreach ($this->importableCustomFieldDefinitions() as $definition) {
            $setName = (string) ($definition->fieldSet?->name ?: 'Custom fields');
            $options[] = [
                'value' => self::CUSTOM_FIELD_PREFIX.$definition->id,
                'label' => $setName.': '.$definition->name,
            ];
        }

        return $options;
    }

    /**
     * Ensure each target field is mapped from at most one source column.
     * create_new_field may be selected multiple times (once per source column).
     *
     * @param  array<string, string|null>  $mapping
     * @return array<string, string>
     */
    public function validateMapping(array $mapping): array
    {
        $cleaned = [];
        $usedTargets = [];
        $customFieldIds = $this->importableCustomFieldDefinitionIds();
        $customFieldLabels = $this->customFieldMappingLabels();

        foreach ($mapping as $column => $field) {
            if ($field === null || $field === '') {
                continue;
            }

            if ($field === self::CREATE_NEW_FIELD) {
                $cleaned[(string) $column] = $field;

                continue;
            }

            if (! array_key_exists($field, self::ASSET_FIELDS) && ! $this->isCustomFieldMapping($field)) {
                throw ValidationException::withMessages([
                    "mapping.{$column}" => "Unknown target field [{$field}].",
                ]);
            }

            if ($this->isCustomFieldMapping($field)) {
                $definitionId = $this->customFieldDefinitionIdFromMapping($field);
                if ($definitionId === null || ! isset($customFieldIds[$definitionId])) {
                    throw ValidationException::withMessages([
                        "mapping.{$column}" => "Unknown target field [{$field}].",
                    ]);
                }
            }

            if (isset($usedTargets[$field])) {
                $label = self::ASSET_FIELDS[$field]
                    ?? $customFieldLabels[$field]
                    ?? $field;

                throw ValidationException::withMessages([
                    "mapping.{$column}" => $label.' is already mapped from column "'.$usedTargets[$field].'". Each field can only be used once.',
                ]);
            }

            $usedTargets[$field] = (string) $column;
            $cleaned[(string) $column] = $field;
        }

        $missing = [];
        if (! in_array('name', $cleaned, true)) {
            $missing[] = 'Map a column to Name before importing.';
        }
        if (! in_array('item_type', $cleaned, true)) {
            $missing[] = 'Map a column to Item Type before importing.';
        }
        if ($missing !== []) {
            throw ValidationException::withMessages([
                'mapping' => $missing,
            ]);
        }

        return $cleaned;
    }

    /**
     * Persist a confirmed mapping so later uploads can reuse column → field choices.
     *
     * @param  array<string, string>  $mapping
     */
    public function rememberMapping(array $mapping, ?int $userId = null): void
    {
        session(['import.learned_mapping' => $mapping]);

        if ($userId) {
            Cache::forever($this->learnedMappingCacheKey($userId), $mapping);
        }
    }

    /**
     * @return array<string, string>
     */
    public function recalledMapping(?int $userId = null): array
    {
        $fromSession = session('import.learned_mapping');
        if (is_array($fromSession) && $fromSession !== []) {
            return $fromSession;
        }

        if ($userId) {
            $fromCache = Cache::get($this->learnedMappingCacheKey($userId), []);

            return is_array($fromCache) ? $fromCache : [];
        }

        return [];
    }

    /**
     * Suggest mappings for spreadsheet columns, preferring previously used mappings.
     *
     * @param  list<string|null>  $columns
     * @param  array<string, string>  $previous
     * @return array<string, string>
     */
    public function suggestMappings(array $columns, array $previous = []): array
    {
        $columns = array_values(array_filter(array_map(
            fn ($column) => is_string($column) ? $column : (string) $column,
            $columns
        ), fn (string $column) => $column !== ''));

        $suggestions = array_fill_keys($columns, '');
        $usedFields = [];

        $previousByNormalized = [];
        foreach ($previous as $column => $field) {
            if (! is_string($field) || $field === '') {
                continue;
            }
            $previousByNormalized[$this->normalizeColumnName((string) $column)] = $field;
        }

        foreach ($columns as $column) {
            $field = $previousByNormalized[$this->normalizeColumnName($column)] ?? null;
            if (! $this->isAssignableSuggestion($field, $usedFields)) {
                continue;
            }

            $suggestions[$column] = $field;
            if ($field !== self::CREATE_NEW_FIELD) {
                $usedFields[$field] = true;
            }
        }

        foreach ($columns as $column) {
            if ($suggestions[$column] !== '') {
                continue;
            }

            $guess = $this->fuzzyGuessField($column, $usedFields);
            if ($guess === null) {
                continue;
            }

            $suggestions[$column] = $guess;
            if ($guess !== self::CREATE_NEW_FIELD) {
                $usedFields[$guess] = true;
            }
        }

        return $suggestions;
    }

    protected function learnedMappingCacheKey(int $userId): string
    {
        return 'import.learned_mapping.'.$userId;
    }

    protected function normalizeColumnName(string $column): string
    {
        return Str::slug(Str::lower(trim($column)), '_');
    }

    /**
     * @param  array<string, bool>  $usedFields
     */
    protected function isAssignableSuggestion(?string $field, array $usedFields): bool
    {
        if ($field === null || $field === '') {
            return false;
        }

        if ($field === self::CREATE_NEW_FIELD) {
            return true;
        }

        if (array_key_exists($field, self::ASSET_FIELDS)) {
            return ! isset($usedFields[$field]);
        }

        if ($this->isCustomFieldMapping($field)) {
            $definitionId = $this->customFieldDefinitionIdFromMapping($field);
            if ($definitionId === null || ! isset($this->importableCustomFieldDefinitionIds()[$definitionId])) {
                return false;
            }

            return ! isset($usedFields[$field]);
        }

        return false;
    }

    /**
     * @param  array<string, bool>  $usedFields
     */
    protected function fuzzyGuessField(string $column, array $usedFields): ?string
    {
        $normalizedColumn = $this->normalizeColumnName($column);
        $hay = Str::lower(trim($column));
        // Maatwebsite HeadingRowImport slugifies headers (Item Type → item_type);
        // compare against space-separated forms so both styles match.
        $hayAsWords = str_replace('_', ' ', $normalizedColumn);

        // Prefer exact matches on core asset field keys / normalized labels.
        foreach (self::ASSET_FIELDS as $field => $label) {
            if (isset($usedFields[$field])) {
                continue;
            }

            if (
                $normalizedColumn === $field
                || $normalizedColumn === $this->normalizeColumnName($label)
            ) {
                return $field;
            }
        }

        // Fuzzy match human-readable (and slugified) headers to core fields first.
        foreach (self::ASSET_FIELDS as $field => $label) {
            if (isset($usedFields[$field])) {
                continue;
            }

            $fieldAsWords = str_replace('_', ' ', $field);
            $labelLower = Str::lower($label);

            if (
                str_contains($hayAsWords, $fieldAsWords)
                || str_contains($hayAsWords, $labelLower)
                || str_contains($hay, $fieldAsWords)
                || str_contains($hay, $labelLower)
            ) {
                return $field;
            }
        }

        // Custom fields: require strong equality so short labels (e.g. "Address")
        // cannot steal core identifier columns like ip_address / mac_address.
        foreach ($this->importableCustomFieldDefinitions() as $definition) {
            $mappingKey = self::CUSTOM_FIELD_PREFIX.$definition->id;
            if (isset($usedFields[$mappingKey])) {
                continue;
            }

            $definitionName = trim((string) $definition->name);
            $setName = trim((string) ($definition->fieldSet?->name ?: ''));
            $fullLabel = $setName !== ''
                ? $setName.': '.$definitionName
                : $definitionName;

            $definitionSlug = $this->normalizeColumnName($definitionName);
            $fullLabelSlug = $this->normalizeColumnName($fullLabel);

            if (
                ($definitionSlug !== '' && $normalizedColumn === $definitionSlug)
                || ($fullLabelSlug !== '' && $normalizedColumn === $fullLabelSlug)
            ) {
                return $mappingKey;
            }
        }

        return null;
    }

    /**
     * @param  Collection<int, Collection<string, mixed>|array<string, mixed>>  $rows
     * @param  array<string, string>  $mapping
     * @return list<array{
     *     index: int,
     *     payload: array<string, mixed>,
     *     custom_fields: array<string, string>,
     *     mapped_custom_fields: array<int, string>,
     *     item_type_name: string,
     *     location_building: ?string,
     *     location_level: ?string,
     *     location_room: ?string,
     *     location_rack: ?string,
     *     existing_id: ?int,
     *     matched_on: list<string>,
     *     existing_summary: ?array<string, mixed>
     * }>
     */
    public function prepareRows(Collection $rows, array $mapping): array
    {
        $drafts = [];

        // #region agent log
        $__dbgPrepT0 = microtime(true);
        $__dbgMatchHits = 0;
        $__dbgSkipped = 0;
        $__dbgLog = function (string $message, array $data = []) use ($__dbgPrepT0): void {
            $payload = [
                'sessionId' => 'd766ca',
                'runId' => 'post-fix',
                'hypothesisId' => 'B',
                'location' => 'ImportService.php:prepareRows',
                'message' => $message,
                'data' => array_merge(['elapsed_ms' => (int) round((microtime(true) - $__dbgPrepT0) * 1000)], $data),
                'timestamp' => (int) round(microtime(true) * 1000),
            ];
            @file_put_contents(base_path('debug-d766ca.log'), json_encode($payload)."\n", FILE_APPEND);
        };
        $__dbgLog('prepareRows_start', ['input_rows' => $rows->count()]);
        // #endregion

        foreach ($rows as $index => $row) {
            $row = Collection::make($row);
            $payload = [];
            $customFields = [];
            $mappedCustomFields = [];
            $itemTypeName = null;
            $building = null;
            $level = null;
            $room = null;
            $rack = null;

            foreach ($mapping as $column => $field) {
                $value = $this->cellValue($row, (string) $column);
                if ($value === null || $value === '') {
                    continue;
                }

                if ($field === self::CREATE_NEW_FIELD) {
                    $customFields[(string) $column] = $this->limitImportString((string) $value, self::ASSET_TEXT_MAX_LENGTH);

                    continue;
                }

                if ($this->isCustomFieldMapping($field)) {
                    $definitionId = $this->customFieldDefinitionIdFromMapping($field);
                    if ($definitionId !== null) {
                        $mappedCustomFields[$definitionId] = $this->limitImportString((string) $value, self::ASSET_TEXT_MAX_LENGTH);
                    }

                    continue;
                }

                if ($field === 'item_type') {
                    $itemTypeName = $this->limitImportString((string) $value, self::META_NAME_MAX_LENGTH);

                    continue;
                }

                if ($field === 'location_building') {
                    $building = $this->limitImportString((string) $value, self::META_NAME_MAX_LENGTH);

                    continue;
                }

                if ($field === 'location_level') {
                    $level = $this->limitImportString((string) $value, self::META_NAME_MAX_LENGTH);

                    continue;
                }

                if ($field === 'location_room') {
                    $room = $this->limitImportString((string) $value, self::META_NAME_MAX_LENGTH);

                    continue;
                }

                if ($field === 'location_rack') {
                    $rack = $this->limitImportString((string) $value, self::META_NAME_MAX_LENGTH);

                    continue;
                }

                if ($field === 'status') {
                    $slug = Str::slug((string) $value, '_');
                    $value = AssetStatus::tryFrom($slug)?->value ?? AssetStatus::Available->value;
                }

                if (in_array($field, self::DATE_FIELDS, true)) {
                    // #region agent log
                    $__dbgRawDate = $value;
                    // #endregion
                    $value = $this->normalizeImportDate($value, $field);
                    // #region agent log
                    if ($__dbgRawDate !== $value) {
                        static $__dbgDateLogs = 0;
                        if ($__dbgDateLogs < 5) {
                            $__dbgDateLogs++;
                            @file_put_contents(base_path('debug-d766ca.log'), json_encode([
                                'sessionId' => 'd766ca',
                                'runId' => 'post-fix',
                                'hypothesisId' => 'DATE',
                                'location' => 'ImportService.php:prepareRows',
                                'message' => 'date_normalized',
                                'data' => [
                                    'field' => $field,
                                    'raw' => is_scalar($__dbgRawDate) ? (string) $__dbgRawDate : get_debug_type($__dbgRawDate),
                                    'normalized' => $value,
                                ],
                                'timestamp' => (int) round(microtime(true) * 1000),
                            ])."\n", FILE_APPEND);
                        }
                    }
                    // #endregion
                }

                if (is_string($value)) {
                    $value = $this->sanitizeAssetFieldString($field, $value);
                }

                $payload[$field] = $value;
            }

            if (empty($payload['name']) || ! $itemTypeName) {
                // #region agent log
                $__dbgSkipped++;
                // #endregion

                continue;
            }

            $drafts[] = [
                'index' => (int) $index,
                'payload' => $payload,
                'custom_fields' => $customFields,
                'mapped_custom_fields' => $mappedCustomFields,
                'item_type_name' => $itemTypeName,
                'location_building' => $building,
                'location_level' => $level,
                'location_room' => $room,
                'location_rack' => $rack,
            ];
        }

        // #region agent log
        $__dbgMatchT0 = microtime(true);
        // #endregion

        $indexes = $this->buildExistingMatchIndexes($drafts);

        // #region agent log
        $__dbgMatchMs = (int) round((microtime(true) - $__dbgMatchT0) * 1000);
        $__dbgLog('prepareRows_indexes_built', [
            'draft_count' => count($drafts),
            'skipped' => $__dbgSkipped,
            'index_keys' => array_keys($indexes),
            'index_sizes' => array_map(fn (array $map) => count($map), $indexes),
            'match_ms' => $__dbgMatchMs,
        ]);
        // #endregion

        $prepared = [];

        foreach ($drafts as $draft) {
            $match = $this->findExistingMatch($draft['payload'], $indexes);

            // #region agent log
            if ($match['asset'] !== null) {
                $__dbgMatchHits++;
            }
            // #endregion

            $prepared[] = [
                'index' => $draft['index'],
                'payload' => $draft['payload'],
                'custom_fields' => $draft['custom_fields'],
                'mapped_custom_fields' => $draft['mapped_custom_fields'],
                'item_type_name' => $draft['item_type_name'],
                'location_building' => $draft['location_building'],
                'location_level' => $draft['location_level'],
                'location_room' => $draft['location_room'],
                'location_rack' => $draft['location_rack'],
                'existing_id' => $match['asset']?->id,
                'matched_on' => $match['matched_on'],
                'existing_summary' => $match['asset'] ? [
                    'id' => $match['asset']->id,
                    'name' => $match['asset']->name,
                    'serial_number' => $match['asset']->serial_number,
                    'fmi_ast' => $match['asset']->fmi_ast,
                    'mac_address' => $match['asset']->mac_address,
                    'rig_tag' => $match['asset']->rig_tag,
                    'tp_barcode' => $match['asset']->tp_barcode,
                    'ip_address' => $match['asset']->ip_address,
                ] : null,
            ];
        }

        // #region agent log
        $__dbgLog('prepareRows_complete', [
            'processed' => count($prepared),
            'skipped' => $__dbgSkipped,
            'prepared' => count($prepared),
            'match_hits' => $__dbgMatchHits,
            'match_ms' => $__dbgMatchMs,
        ]);
        // #endregion

        return $prepared;
    }

    /**
     * @param  list<array<string, mixed>>  $prepared
     * @param  array<int|string, string>  $actions  row index => update|replace|skip|create
     * @param  array{
     *     destination?: 'none'|'existing'|'new',
     *     asset_list_id?: int|null,
     *     new_list_name?: string|null,
     *     new_list_type?: 'inventory'|'event'|null,
     *     include_updated?: bool,
     * }  $listOptions
     * @return array{
     *     created: int,
     *     updated: int,
     *     replaced: int,
     *     skipped: int,
     *     added_to_list: int,
     *     list_id: ?int,
     *     list_name: ?string
     * }
     */
    public function commit(
        array $prepared,
        array $actions = [],
        bool $dryRun = false,
        string $defaultDuplicateAction = 'update',
        array $listOptions = [],
        ?int $userId = null,
    ): array {
        // Large imports exceed default 30s PHP limits (per-row create + audit log).
        if (! $dryRun) {
            set_time_limit(0);
        }

        $counts = [
            'created' => 0,
            'updated' => 0,
            'replaced' => 0,
            'skipped' => 0,
            'added_to_list' => 0,
            'list_id' => null,
            'list_name' => null,
        ];
        $assetsForList = [];
        $includeUpdated = (bool) ($listOptions['include_updated'] ?? false);
        $listDestination = $listOptions['destination'] ?? 'none';

        // #region agent log
        $__dbgCommitT0 = microtime(true);
        $__dbgRowsSeen = 0;
        $__dbgLogCommit = function (string $message, array $data = []) use ($__dbgCommitT0, &$counts): void {
            $payload = [
                'sessionId' => 'd766ca',
                'runId' => 'post-fix',
                'hypothesisId' => 'COMMIT',
                'location' => 'ImportService.php:commit',
                'message' => $message,
                'data' => array_merge([
                    'elapsed_ms' => (int) round((microtime(true) - $__dbgCommitT0) * 1000),
                    'created' => $counts['created'],
                    'updated' => $counts['updated'],
                    'replaced' => $counts['replaced'],
                    'skipped' => $counts['skipped'],
                ], $data),
                'timestamp' => (int) round(microtime(true) * 1000),
            ];
            @file_put_contents(base_path('debug-d766ca.log'), json_encode($payload)."\n", FILE_APPEND);
        };
        $__dbgLogCommit('commit_start', [
            'prepared_count' => count($prepared),
            'dry_run' => $dryRun,
            'default_action' => $defaultDuplicateAction,
            'time_limit' => (int) ini_get('max_execution_time'),
        ]);
        // #endregion

        $processRows = function (array $chunk) use (
            $actions,
            $dryRun,
            $defaultDuplicateAction,
            $includeUpdated,
            &$counts,
            &$assetsForList,
            &$__dbgRowsSeen,
            $__dbgLogCommit,
        ): void {
            foreach ($chunk as $row) {
                $existingId = $row['existing_id'] ?? null;

                if ($existingId) {
                    $action = $actions[$row['index']] ?? $defaultDuplicateAction;
                    if (! in_array($action, ['update', 'replace', 'skip', 'create'], true)) {
                        $action = $defaultDuplicateAction;
                    }

                    if ($action === 'skip') {
                        $counts['skipped']++;

                        // #region agent log
                        $__dbgRowsSeen++;
                        if ($__dbgRowsSeen === 1 || $__dbgRowsSeen % 250 === 0) {
                            $__dbgLogCommit('commit_progress', ['rows_seen' => $__dbgRowsSeen]);
                        }
                        // #endregion

                        continue;
                    }

                    // False match: create a new asset; leave the matched existing asset unchanged.
                    if ($action === 'create') {
                        if ($dryRun) {
                            $counts['created']++;
                            $assetsForList[] = ['id' => null, 'kind' => 'created'];

                            // #region agent log
                            $__dbgRowsSeen++;
                            // #endregion

                            continue;
                        }

                        $asset = $this->applyRow(null, $row, false);
                        $counts['created']++;
                        $assetsForList[] = ['id' => $asset->id, 'kind' => 'created'];

                        // #region agent log
                        $__dbgRowsSeen++;
                        if ($__dbgRowsSeen === 1 || $__dbgRowsSeen % 250 === 0) {
                            $__dbgLogCommit('commit_progress', ['rows_seen' => $__dbgRowsSeen]);
                        }
                        // #endregion

                        continue;
                    }

                    if ($dryRun) {
                        $counts[$action === 'replace' ? 'replaced' : 'updated']++;
                        if ($includeUpdated) {
                            $assetsForList[] = ['id' => (int) $existingId, 'kind' => 'updated'];
                        }

                        // #region agent log
                        $__dbgRowsSeen++;
                        // #endregion

                        continue;
                    }

                    $asset = Asset::query()->find($existingId);
                    if (! $asset) {
                        $counts['skipped']++;

                        // #region agent log
                        $__dbgRowsSeen++;
                        // #endregion

                        continue;
                    }

                    // #region agent log
                    $__dbgCommitPayload = [
                        'sessionId' => 'ee61fc',
                        'runId' => 'pre-fix',
                        'hypothesisId' => 'H1',
                        'location' => 'ImportService.php:commit',
                        'message' => 'duplicate_action_resolved',
                        'data' => [
                            'row_index' => $row['index'] ?? null,
                            'existing_id' => (int) $existingId,
                            'action' => $action,
                            'replace_flag' => $action === 'replace',
                            'payload_keys' => array_keys($row['payload'] ?? []),
                            'payload_empty_keys' => array_values(array_filter(array_keys($row['payload'] ?? []), function ($k) use ($row) {
                                $v = $row['payload'][$k] ?? null;

                                return $v === null || $v === '';
                            })),
                        ],
                        'timestamp' => (int) round(microtime(true) * 1000),
                    ];
                    @file_put_contents(base_path('debug-ee61fc.log'), json_encode($__dbgCommitPayload)."\n", FILE_APPEND);
                    // #endregion

                    $asset = $this->applyRow($asset, $row, $action === 'replace');
                    $counts[$action === 'replace' ? 'replaced' : 'updated']++;
                    if ($includeUpdated) {
                        $assetsForList[] = ['id' => $asset->id, 'kind' => 'updated'];
                    }

                    // #region agent log
                    $__dbgRowsSeen++;
                    if ($__dbgRowsSeen === 1 || $__dbgRowsSeen % 250 === 0) {
                        $__dbgLogCommit('commit_progress', ['rows_seen' => $__dbgRowsSeen]);
                    }
                    // #endregion

                    continue;
                }

                if ($dryRun) {
                    $counts['created']++;
                    $assetsForList[] = ['id' => null, 'kind' => 'created'];

                    // #region agent log
                    $__dbgRowsSeen++;
                    // #endregion

                    continue;
                }

                $asset = $this->applyRow(null, $row, false);
                $counts['created']++;
                $assetsForList[] = ['id' => $asset->id, 'kind' => 'created'];

                // #region agent log
                $__dbgRowsSeen++;
                if ($__dbgRowsSeen === 1 || $__dbgRowsSeen % 250 === 0) {
                    $__dbgLogCommit('commit_progress', ['rows_seen' => $__dbgRowsSeen]);
                }
                // #endregion
            }
        };

        $chunkSize = max(1, PerformanceLimits::int('import_commit_chunk'));

        if ($dryRun) {
            $processRows($prepared);
        } else {
            foreach (array_chunk($prepared, $chunkSize) as $chunk) {
                DB::transaction(fn () => $processRows($chunk));
            }
        }

        // #region agent log
        $__dbgLogCommit('commit_complete', [
            'rows_seen' => $__dbgRowsSeen,
            'item_type_cache' => count($this->itemTypeResolutionCache),
            'location_cache' => count($this->locationResolutionCache),
            'location_path_cache' => count($this->locationPathResolutionCache),
        ]);
        // #endregion

        if ($listDestination === 'none' || $assetsForList === []) {
            return $counts;
        }

        if ($dryRun) {
            $counts['added_to_list'] = count($assetsForList);

            return $counts;
        }

        DB::transaction(function () use ($listOptions, $userId, $assetsForList, &$counts): void {
            $list = $this->resolveImportTargetList($listOptions, $userId);
            if (! $list) {
                return;
            }

            $counts['list_id'] = $list->id;
            $counts['list_name'] = $list->name;
            $counts['added_to_list'] = $this->addAssetsToList(
                $list,
                array_values(array_filter(array_column($assetsForList, 'id'))),
                $userId,
            );
        });

        return $counts;
    }

    /**
     * @param  array{
     *     destination?: 'none'|'existing'|'new',
     *     asset_list_id?: int|null,
     *     new_list_name?: string|null,
     *     new_list_type?: 'inventory'|'event'|null,
     * }  $listOptions
     */
    protected function resolveImportTargetList(array $listOptions, ?int $userId): ?AssetList
    {
        $destination = $listOptions['destination'] ?? 'none';

        if ($destination === 'existing') {
            $listId = (int) ($listOptions['asset_list_id'] ?? 0);

            return $listId > 0 ? AssetList::query()->find($listId) : null;
        }

        if ($destination === 'new') {
            $name = trim((string) ($listOptions['new_list_name'] ?? ''));
            $type = $listOptions['new_list_type'] ?? null;

            if ($name === '' || ! in_array($type, ['inventory', 'event'], true)) {
                return null;
            }

            return AssetList::create([
                'name' => $name,
                'type' => $type,
                'created_by' => $userId,
            ]);
        }

        return null;
    }

    /**
     * @param  list<int>  $assetIds
     */
    protected function addAssetsToList(AssetList $list, array $assetIds, ?int $userId): int
    {
        $assetIds = array_values(array_unique(array_map('intval', $assetIds)));
        if ($assetIds === []) {
            return 0;
        }

        $assets = Asset::query()->whereIn('id', $assetIds)->get()->keyBy('id');
        $added = 0;

        foreach ($assetIds as $assetId) {
            $asset = $assets->get($assetId);
            if (! $asset) {
                continue;
            }

            $item = AssetListItem::query()->firstOrCreate(
                [
                    'asset_list_id' => $list->id,
                    'asset_id' => $asset->id,
                ],
                [
                    'quantity' => max(1, (int) ($asset->quantity ?? 1)),
                    'is_child_expand' => (bool) $asset->parent_id,
                    'added_by' => $userId,
                ]
            );

            if ($item->wasRecentlyCreated) {
                $added++;
            }
        }

        return $added;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function applyRow(?Asset $existing, array $row, bool $replace): Asset
    {
        $itemTypeName = $this->limitImportString((string) ($row['item_type_name'] ?? ''), self::META_NAME_MAX_LENGTH);
        $itemType = $this->resolveItemType($itemTypeName);
        $locationId = $this->resolveLocation(
            $this->nullableLimit($row['location_building'] ?? null, self::META_NAME_MAX_LENGTH),
            $this->nullableLimit($row['location_level'] ?? null, self::META_NAME_MAX_LENGTH),
            $this->nullableLimit($row['location_room'] ?? null, self::META_NAME_MAX_LENGTH),
            $this->nullableLimit($row['location_rack'] ?? null, self::META_NAME_MAX_LENGTH),
        );

        $payload = $row['payload'];
        // #region agent log
        $__dbgStatusInRow = array_key_exists('status', $payload);
        $__dbgMfrInRow = array_key_exists('manufacturer', $payload);
        $__dbgModelInRow = array_key_exists('model', $payload);
        $__dbgNameLenBefore = is_string($payload['name'] ?? null) ? mb_strlen($payload['name']) : null;
        $__dbgBefore = $existing ? [
            'name' => $existing->name,
            'status' => $existing->status?->value ?? $existing->status,
            'notes' => $existing->notes,
            'description' => $existing->description,
            'serial_number' => $existing->serial_number,
            'fmi_ast' => $existing->fmi_ast,
            'tp_barcode' => $existing->tp_barcode,
            'manufacturer_id' => $existing->manufacturer_id,
            'manufacturer_model_id' => $existing->manufacturer_model_id,
            'location_id' => $existing->location_id,
            'supplier' => $existing->supplier,
            'quantity' => $existing->quantity,
        ] : null;
        // #endregion

        $payload = $this->sanitizeAssetPayloadStrings($payload);

        foreach (self::DATE_FIELDS as $dateField) {
            if (array_key_exists($dateField, $payload)) {
                $payload[$dateField] = $this->normalizeImportDate($payload[$dateField], $dateField);
            }
        }

        $payload['item_type_id'] = $itemType->id;
        if ($locationId !== null) {
            $payload['location_id'] = $locationId;
        }
        $payload['status'] = $payload['status'] ?? AssetStatus::Available->value;

        if (array_key_exists('manufacturer', $payload) || array_key_exists('model', $payload)) {
            $this->manufacturerCatalogue ??= app(ManufacturerCatalogueService::class);
            $catalogueIds = $this->manufacturerCatalogue->resolveFromNames(
                array_key_exists('manufacturer', $payload) ? (string) ($payload['manufacturer'] ?? '') : null,
                array_key_exists('model', $payload) ? (string) ($payload['model'] ?? '') : null,
            );
            unset($payload['manufacturer'], $payload['model']);
            $payload['manufacturer_id'] = $catalogueIds['manufacturer_id'];
            $payload['manufacturer_model_id'] = $catalogueIds['manufacturer_model_id'];
        }

        // #region agent log
        if ($existing) {
            $__dbgNullPayloadKeys = [];
            foreach ($payload as $__dbgK => $__dbgV) {
                if ($__dbgV === null || $__dbgV === '') {
                    $__dbgNullPayloadKeys[] = $__dbgK;
                }
            }
            $__dbgApplyLog = [
                'sessionId' => 'ee61fc',
                'runId' => 'pre-fix',
                'hypothesisId' => 'H2-H5',
                'location' => 'ImportService.php:applyRow',
                'message' => 'applyRow_before_persist',
                'data' => [
                    'existing_id' => $existing->id,
                    'replace' => $replace,
                    'status_in_row_payload' => $__dbgStatusInRow,
                    'status_being_written' => $payload['status'] ?? null,
                    'mfr_in_row' => $__dbgMfrInRow,
                    'model_in_row' => $__dbgModelInRow,
                    'payload_keys' => array_keys($payload),
                    'null_or_empty_payload_keys' => $__dbgNullPayloadKeys,
                    'before' => $__dbgBefore,
                ],
                'timestamp' => (int) round(microtime(true) * 1000),
            ];
            @file_put_contents(base_path('debug-ee61fc.log'), json_encode($__dbgApplyLog)."\n", FILE_APPEND);
        }
        // #endregion

        // #region agent log
        $__dbgLen = static function (mixed $v): ?int {
            return is_string($v) ? mb_strlen($v) : null;
        };
        $__dbgOverLimit = [];
        foreach (self::ASSET_STRING_MAX_LENGTHS as $__dbgField => $__dbgMax) {
            $__dbgVal = $payload[$__dbgField] ?? null;
            if (is_string($__dbgVal) && mb_strlen($__dbgVal) > $__dbgMax) {
                $__dbgOverLimit[$__dbgField] = mb_strlen($__dbgVal);
            }
        }
        @file_put_contents(base_path('debug-704ea9.log'), json_encode([
            'sessionId' => '704ea9',
            'runId' => 'post-fix',
            'hypothesisId' => 'H1',
            'location' => 'ImportService.php:applyRow:before_persist',
            'message' => 'payload_string_lengths_after_sanitize',
            'data' => [
                'existing_id' => $existing?->id,
                'replace' => $replace,
                'will_create' => $existing === null,
                'name_len_before_sanitize' => $__dbgNameLenBefore,
                'name_len' => $__dbgLen($payload['name'] ?? null),
                'description_len' => $__dbgLen($payload['description'] ?? null),
                'notes_len' => $__dbgLen($payload['notes'] ?? null),
                'supplier_len' => $__dbgLen($payload['supplier'] ?? null),
                'name_preview' => is_string($payload['name'] ?? null) ? mb_substr($payload['name'], 0, 80) : null,
                'fields_over_column_limit' => $__dbgOverLimit,
                'name_was_truncated' => ($__dbgNameLenBefore ?? 0) > 255,
            ],
            'timestamp' => (int) round(microtime(true) * 1000),
        ])."\n", FILE_APPEND);
        // #endregion

        if ($existing && $replace) {
            $existing = $this->replaceAsset($existing, $payload);
        } elseif ($existing) {
            $existing->update($payload);
        } else {
            $existing = Asset::create($payload);
        }

        // #region agent log
        if ($__dbgBefore !== null) {
            $existing->refresh();
            $__dbgAfter = [
                'name' => $existing->name,
                'status' => $existing->status?->value ?? $existing->status,
                'notes' => $existing->notes,
                'description' => $existing->description,
                'serial_number' => $existing->serial_number,
                'fmi_ast' => $existing->fmi_ast,
                'tp_barcode' => $existing->tp_barcode,
                'manufacturer_id' => $existing->manufacturer_id,
                'manufacturer_model_id' => $existing->manufacturer_model_id,
                'location_id' => $existing->location_id,
                'supplier' => $existing->supplier,
                'quantity' => $existing->quantity,
            ];
            $__dbgCleared = [];
            foreach ($__dbgBefore as $__dbgField => $__dbgOld) {
                $__dbgNew = $__dbgAfter[$__dbgField] ?? null;
                $__dbgHadValue = $__dbgOld !== null && $__dbgOld !== '';
                $__dbgNowEmpty = $__dbgNew === null || $__dbgNew === '';
                if ($__dbgHadValue && $__dbgNowEmpty) {
                    $__dbgCleared[] = $__dbgField;
                }
            }
            $__dbgAfterLog = [
                'sessionId' => 'ee61fc',
                'runId' => 'pre-fix',
                'hypothesisId' => 'H2-H5',
                'location' => 'ImportService.php:applyRow',
                'message' => 'applyRow_after_persist',
                'data' => [
                    'existing_id' => $existing->id,
                    'replace' => $replace,
                    'cleared_fields' => $__dbgCleared,
                    'after' => $__dbgAfter,
                ],
                'timestamp' => (int) round(microtime(true) * 1000),
            ];
            @file_put_contents(base_path('debug-ee61fc.log'), json_encode($__dbgAfterLog)."\n", FILE_APPEND);
        }
        // #endregion

        $this->syncImportedCustomFields($existing, $itemType, $row['custom_fields'] ?? [], $replace);
        $this->syncMappedCustomFields($existing, $itemType, $row['mapped_custom_fields'] ?? [], $replace);

        return $existing;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function replaceAsset(Asset $asset, array $payload): Asset
    {
        $blank = [
            'location_id' => null,
            'parent_id' => null,
            'description' => null,
            'manufacturer_id' => null,
            'manufacturer_model_id' => null,
            'serial_number' => null,
            'purchase_date' => null,
            'warranty_expiry' => null,
            'supplier' => null,
            'replacement_cost' => null,
            'fmi_ast' => null,
            'tp_barcode' => null,
            'rig_tag' => null,
            'ip_address' => null,
            'mac_address' => null,
            'test_tag_expiry' => null,
            'quantity' => 1,
            'notes' => null,
            'status' => AssetStatus::Available->value,
        ];

        $asset->update(array_merge($blank, $payload));

        return $asset->fresh();
    }

    /**
     * @param  array<string, string>  $customFields  source column => value
     */
    protected function syncImportedCustomFields(Asset $asset, ItemType $itemType, array $customFields, bool $replace): void
    {
        if ($customFields === []) {
            return;
        }

        $set = $this->importedFieldSet();
        if (! $itemType->fieldSets()->where('custom_field_sets.id', $set->id)->exists()) {
            $itemType->fieldSets()->attach($set->id);
        }

        foreach ($customFields as $column => $value) {
            $definition = $this->resolveImportedFieldDefinition($set, (string) $column);
            AssetCustomFieldValue::query()->updateOrCreate(
                [
                    'asset_id' => $asset->id,
                    'custom_field_definition_id' => $definition->id,
                ],
                ['value' => $value]
            );
        }

        if ($replace) {
            // Imported-Field values not present in this row are left intact;
            // replace clears core columns only.
        }
    }

    /**
     * @param  array<int, string>  $mappedCustomFields  definition id => value
     */
    protected function syncMappedCustomFields(Asset $asset, ItemType $itemType, array $mappedCustomFields, bool $replace): void
    {
        if ($mappedCustomFields === []) {
            return;
        }

        $definitions = CustomFieldDefinition::query()
            ->with('fieldSet:id,name')
            ->whereIn('id', array_keys($mappedCustomFields))
            ->whereNotIn('type', CustomFieldDefinition::LAYOUT_TYPES)
            ->get()
            ->keyBy('id');

        $attachedSetIds = [];

        foreach ($mappedCustomFields as $definitionId => $value) {
            $definition = $definitions->get((int) $definitionId);
            if (! $definition) {
                continue;
            }

            $setId = (int) $definition->custom_field_set_id;
            if (! isset($attachedSetIds[$setId])) {
                $itemType->fieldSets()->syncWithoutDetaching([$setId]);
                $attachedSetIds[$setId] = true;
            }

            AssetCustomFieldValue::query()->updateOrCreate(
                [
                    'asset_id' => $asset->id,
                    'custom_field_definition_id' => $definition->id,
                ],
                ['value' => $value]
            );
        }

        if ($replace) {
            // Mapped custom field values not present in this row are left intact;
            // replace clears core columns only.
        }
    }

    public function isCustomFieldMapping(string $field): bool
    {
        return str_starts_with($field, self::CUSTOM_FIELD_PREFIX);
    }

    public function customFieldDefinitionIdFromMapping(string $field): ?int
    {
        if (! $this->isCustomFieldMapping($field)) {
            return null;
        }

        $id = (int) substr($field, strlen(self::CUSTOM_FIELD_PREFIX));

        return $id > 0 ? $id : null;
    }

    /**
     * @return Collection<int, CustomFieldDefinition>
     */
    protected function importableCustomFieldDefinitions(): Collection
    {
        return $this->importableCustomFieldDefinitionsCache ??= CustomFieldDefinition::query()
            ->with('fieldSet:id,name')
            ->whereNotIn('type', CustomFieldDefinition::LAYOUT_TYPES)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->sortBy(fn (CustomFieldDefinition $definition): string => sprintf(
                '%s|%05d|%s|%d',
                (string) ($definition->fieldSet?->name ?: 'Custom fields'),
                (int) $definition->sort_order,
                $definition->name,
                (int) $definition->id,
            ))
            ->values();
    }

    /**
     * @return array<int, true>
     */
    protected function importableCustomFieldDefinitionIds(): array
    {
        return $this->importableCustomFieldDefinitions()
            ->mapWithKeys(fn (CustomFieldDefinition $definition): array => [(int) $definition->id => true])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected function customFieldMappingLabels(): array
    {
        return $this->importableCustomFieldDefinitions()
            ->mapWithKeys(function (CustomFieldDefinition $definition): array {
                $setName = (string) ($definition->fieldSet?->name ?: 'Custom fields');

                return [
                    self::CUSTOM_FIELD_PREFIX.$definition->id => $setName.': '.$definition->name,
                ];
            })
            ->all();
    }

    protected function importedFieldSet(): CustomFieldSet
    {
        return CustomFieldSet::query()->firstOrCreate(
            ['slug' => 'imported-fields'],
            [
                'name' => 'Imported Fields',
                'description' => 'Fields created automatically during spreadsheet import.',
            ]
        );
    }

    protected function resolveImportedFieldDefinition(CustomFieldSet $set, string $column): CustomFieldDefinition
    {
        $name = 'Imported-Field:'.$column;
        $slug = Str::slug($name, '_');

        return CustomFieldDefinition::query()->firstOrCreate(
            [
                'custom_field_set_id' => $set->id,
                'slug' => $slug,
            ],
            [
                'name' => $name,
                'type' => 'text',
                'sort_order' => (int) $set->definitions()->count(),
            ]
        );
    }

    public function resolveItemType(string $name): ItemType
    {
        $name = trim($name);
        $cacheKey = Str::lower($name);

        if (isset($this->itemTypeResolutionCache[$cacheKey])) {
            return $this->itemTypeResolutionCache[$cacheKey];
        }

        $slug = Str::slug($name);

        $existing = ItemType::query()
            ->where(function ($query) use ($name, $slug) {
                $query->where('name', $name)
                    ->orWhereRaw('LOWER(name) = ?', [Str::lower($name)])
                    ->orWhere('slug', $slug);
            })
            ->first();

        if ($existing) {
            return $this->itemTypeResolutionCache[$cacheKey] = $existing;
        }

        $aliased = ItemTypeAlias::resolveItemType($name);
        if ($aliased) {
            return $this->itemTypeResolutionCache[$cacheKey] = $aliased;
        }

        return $this->itemTypeResolutionCache[$cacheKey] = ItemType::create([
            'name' => $name,
            'slug' => $slug !== '' ? $slug : Str::slug($name.'-'.Str::random(4)),
        ]);
    }

    public function resolveLocation(?string $building, ?string $level, ?string $room, ?string $rack): ?int
    {
        $building = $this->nullableTrim($building);
        $level = $this->nullableTrim($level);
        $room = $this->nullableTrim($room);
        $rack = $this->nullableTrim($rack);

        if (! $building && ! $level && ! $room && ! $rack) {
            return null;
        }

        $pathKey = implode("\0", [
            $building ?? '',
            $level ?? '',
            $room ?? '',
            $rack ?? '',
        ]);

        if (array_key_exists($pathKey, $this->locationPathResolutionCache)) {
            return $this->locationPathResolutionCache[$pathKey];
        }

        $parentId = null;
        $resolvedId = null;

        if ($building) {
            $location = $this->firstOrCreateLocation($building, LocationType::Building, $parentId);
            $parentId = $location->id;
            $resolvedId = $location->id;
        }

        if ($level) {
            $location = $this->firstOrCreateLocation($level, LocationType::Level, $parentId);
            $parentId = $location->id;
            $resolvedId = $location->id;
        }

        if ($room) {
            $location = $this->firstOrCreateLocation($room, LocationType::Room, $parentId);
            $parentId = $location->id;
            $resolvedId = $location->id;
        }

        if ($rack) {
            $location = $this->firstOrCreateLocation($rack, LocationType::Rack, $parentId);
            $resolvedId = $location->id;
        }

        return $this->locationPathResolutionCache[$pathKey] = $resolvedId;
    }

    protected function firstOrCreateLocation(string $name, LocationType $type, ?int $parentId): Location
    {
        $cacheKey = $type->value.'|'.$name.'|'.($parentId ?? 'null');

        if (isset($this->locationResolutionCache[$cacheKey])) {
            return $this->locationResolutionCache[$cacheKey];
        }

        $baseQuery = Location::query()
            ->where('name', $name)
            ->where('type', $type->value);

        $exactQuery = (clone $baseQuery);
        if ($parentId) {
            $exactQuery->where('parent_id', $parentId);
        } else {
            $exactQuery->whereNull('parent_id');
        }

        $existing = $exactQuery->first();

        // Import maps Building/Level/Room/Rack only (no Site). Buildings and levels often
        // live under a Site, so when no parent is known, reuse an existing name+type match
        // anywhere in the tree.
        if (! $existing && $parentId === null) {
            $existing = (clone $baseQuery)->orderBy('id')->first();
        }

        if ($existing) {
            return $this->locationResolutionCache[$cacheKey] = $existing;
        }

        $aliased = LocationAlias::resolveLocation($name, $type);
        if ($aliased) {
            return $this->locationResolutionCache[$cacheKey] = $aliased;
        }

        return $this->locationResolutionCache[$cacheKey] = Location::create([
            'name' => $name,
            'type' => $type,
            'parent_id' => $parentId,
            'is_portable' => false,
        ]);
    }

    /**
     * Normalise spreadsheet date cells to Y-m-d for Eloquent date casts.
     * Accepts day-first strings (common in AU/NZ exports), ISO dates, Excel serials, and DateTime.
     */
    protected function normalizeImportDate(mixed $value, string $field = 'date'): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::parse($value)->toDateString();
        }

        if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value) && ! preg_match('/[^\d.]/', trim($value)))) {
            $numeric = (float) $value;
            // Excel serials are typically 1..~60000 (1900–2064); reject tiny integers that are not dates.
            if ($numeric >= 1 && $numeric < 100000) {
                try {
                    return Carbon::instance(ExcelDate::excelToDateTimeObject($numeric))->toDateString();
                } catch (Throwable) {
                    // Fall through to string parsing.
                }
            }
        }

        $string = trim((string) $value);
        if ($string === '') {
            return null;
        }

        $formats = [
            'Y-m-d',
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'Y/m/d',
            'Y/m/d H:i:s',
            'Y/m/d H:i',
            'd/m/Y',
            'd/m/Y H:i:s',
            'd/m/Y H:i',
            'd/m/Y G:i',
            'd/m/Y G:i:s',
            'd-m-Y',
            'd-m-Y H:i:s',
            'd-m-Y H:i',
            'd.m.Y',
            'd.m.Y H:i:s',
            'd.m.Y H:i',
            'm/d/Y',
            'm/d/Y H:i:s',
            'm/d/Y H:i',
            'm/d/Y G:i',
            'j/n/Y',
            'j/n/Y G:i',
        ];

        foreach ($formats as $format) {
            try {
                $parsed = Carbon::createFromFormat('!'.$format, $string);
                if ($parsed instanceof Carbon) {
                    return $parsed->toDateString();
                }
            } catch (Throwable) {
                // Try the next format.
            }
        }

        try {
            return Carbon::parse($string)->toDateString();
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'file' => sprintf(
                    'Could not read %s value "%s". Use YYYY-MM-DD or DD/MM/YYYY.',
                    self::ASSET_FIELDS[$field] ?? $field,
                    Str::limit($string, 40),
                ),
            ]);
        }
    }

    /**
     * Prefetch existing assets for all duplicate keys in one query per key.
     *
     * @param  list<array{payload: array<string, mixed>}>  $drafts
     * @return array<string, array<string, Asset>> key => (string)value => Asset
     */
    protected function buildExistingMatchIndexes(array $drafts): array
    {
        /** @var array<string, array<string, true>> $valuesByKey */
        $valuesByKey = [];

        foreach ($drafts as $draft) {
            $payload = $draft['payload'];
            foreach (self::DUPLICATE_KEYS as $key) {
                $value = $payload[$key] ?? null;
                if ($value === null || $value === '') {
                    continue;
                }

                $valuesByKey[$key][(string) $value] = true;
            }
        }

        /** @var array<string, array<string, Asset>> $indexes */
        $indexes = [];
        $columns = array_values(array_unique(array_merge(
            ['id', 'name'],
            self::DUPLICATE_KEYS,
        )));

        foreach ($valuesByKey as $key => $valueSet) {
            $values = array_keys($valueSet);
            if ($values === []) {
                continue;
            }

            $found = Asset::query()
                ->select($columns)
                ->whereIn($key, $values)
                ->orderBy('id')
                ->get();

            $map = [];
            foreach ($found as $asset) {
                $lookup = $asset->getAttribute($key);
                if ($lookup === null || $lookup === '') {
                    continue;
                }

                $lookupKey = (string) $lookup;
                // Match query()->where()->first() behaviour: keep the earliest id.
                if (! isset($map[$lookupKey])) {
                    $map[$lookupKey] = $asset;
                }
            }

            $indexes[$key] = $map;
        }

        return $indexes;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, array<string, Asset>>|null  $indexes
     * @return array{asset: ?Asset, matched_on: list<string>}
     */
    protected function findExistingMatch(array $payload, ?array $indexes = null): array
    {
        $matchedOn = [];
        $asset = null;

        foreach (self::DUPLICATE_KEYS as $key) {
            $value = $payload[$key] ?? null;
            if ($value === null || $value === '') {
                continue;
            }

            if ($indexes !== null) {
                $found = $indexes[$key][(string) $value] ?? null;
            } else {
                $found = Asset::query()->where($key, $value)->orderBy('id')->first();
            }

            if ($found) {
                if ($asset && $asset->id !== $found->id) {
                    // Prefer the first match; still record the key that hit.
                    $matchedOn[] = $key;

                    continue;
                }
                $asset = $found;
                $matchedOn[] = $key;
            }
        }

        return ['asset' => $asset, 'matched_on' => $matchedOn];
    }

    /**
     * Clamp a string to a maximum character length (UTF-8 safe, no ellipsis).
     */
    protected function limitImportString(string $value, int $maxLength): string
    {
        if ($maxLength < 1) {
            return '';
        }

        if (mb_strlen($value) <= $maxLength) {
            return $value;
        }

        return mb_substr($value, 0, $maxLength);
    }

    protected function nullableLimit(mixed $value, int $maxLength): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);
        if ($trimmed === '') {
            return null;
        }

        return $this->limitImportString($trimmed, $maxLength);
    }

    protected function sanitizeAssetFieldString(string $field, string $value): string
    {
        if (isset(self::ASSET_STRING_MAX_LENGTHS[$field])) {
            return $this->limitImportString($value, self::ASSET_STRING_MAX_LENGTHS[$field]);
        }

        if (in_array($field, ['description', 'notes'], true)) {
            return $this->limitImportString($value, self::ASSET_TEXT_MAX_LENGTH);
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function sanitizeAssetPayloadStrings(array $payload): array
    {
        foreach ($payload as $field => $value) {
            if (! is_string($value) || ! is_string($field)) {
                continue;
            }

            $payload[$field] = $this->sanitizeAssetFieldString($field, $value);
        }

        return $payload;
    }

    /**
     * @param  Collection<string, mixed>  $row
     */
    protected function cellValue(Collection $row, string $column): mixed
    {
        $key = Str::slug($column, '_');

        return $row->get($key) ?? $row->get($column) ?? $row->get(Str::lower($column));
    }

    /**
     * Normalise a spreadsheet cell into a short display string for the mapping UI.
     */
    protected function formatExampleCell(mixed $value): ?string
    {
        if ($value === null || is_array($value) || is_object($value)) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        $string = trim((string) $value);

        if ($string === '') {
            return null;
        }

        return Str::limit($string, 80);
    }

    protected function nullableTrim(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
