<?php

namespace App\Http\Controllers;

use App\Enums\FileCategory;
use App\Http\Requests\ImportUploadRequest;
use App\Jobs\ProcessAssetImportJob;
use App\Models\AssetList;
use App\Services\FileAttachmentService;
use App\Services\ImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class ImportController extends Controller
{
    public function __construct(
        protected ImportService $imports,
        protected FileAttachmentService $attachments,
    ) {}

    public function create(): View
    {
        return view('import.create');
    }

    public function upload(ImportUploadRequest $request): View|RedirectResponse
    {
        $uploaded = $request->file('file');
        $path = $uploaded->store('imports');

        try {
            // Prefer the persisted storage path so Excel does not depend on the PHP upload temp file.
            $absolutePath = Storage::path($path);
            $columns = $this->imports->readHeadingColumns($absolutePath);
            $columnExamples = $this->imports->readColumnExamples($absolutePath, $columns);
        } catch (ValidationException $e) {
            Storage::delete($path);

            throw $e;
        }

        $previousPrepared = session('import.prepared_path');
        if (is_string($previousPrepared)) {
            $this->imports->forgetPreparedRows($previousPrepared);
        }

        session([
            'import.path' => $path,
            'import.columns' => $columns,
            'import.original_filename' => $uploaded->getClientOriginalName(),
        ]);
        session()->forget(['import.mapping', 'import.prepared', 'import.prepared_path']);

        $previous = $this->imports->recalledMapping($request->user()?->id);
        $suggestedMappings = $this->imports->suggestMappings($columns, $previous);

        return view('import.map', [
            'columns' => $columns,
            'columnExamples' => $columnExamples,
            'fields' => ImportService::ASSET_FIELDS,
            'fieldOptions' => $this->imports->fieldOptionsForMapping(),
            'createNewField' => ImportService::CREATE_NEW_FIELD,
            'suggestedMappings' => $suggestedMappings,
            'usedRememberedMappings' => $previous !== [],
        ]);
    }

    public function prepare(Request $request): View|RedirectResponse
    {
        // Large spreadsheets exceed default 30s / 128M PHP limits (Excel alone ~100M+).
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        // #region agent log
        $__dbgPrepareT0 = microtime(true);
        $__dbgLog = function (string $hypothesisId, string $message, array $data = []) use ($__dbgPrepareT0): void {
            $payload = [
                'sessionId' => 'd766ca',
                'runId' => 'dup-check-3',
                'hypothesisId' => $hypothesisId,
                'location' => 'ImportController.php:prepare',
                'message' => $message,
                'data' => array_merge([
                    'elapsed_ms' => (int) round((microtime(true) - $__dbgPrepareT0) * 1000),
                    'memory_mb' => (int) round(memory_get_usage(true) / 1048576),
                    'peak_memory_mb' => (int) round(memory_get_peak_usage(true) / 1048576),
                    'memory_limit' => ini_get('memory_limit'),
                ], $data),
                'timestamp' => (int) round(microtime(true) * 1000),
            ];
            @file_put_contents(base_path('debug-d766ca.log'), json_encode($payload)."\n", FILE_APPEND);
        };
        $__dbgLog('P', 'prepare_entered');
        // #endregion

        try {
            $path = session('import.path');
            $columns = session('import.columns', []);

            if (! $path || ! Storage::exists($path)) {
                return redirect()->route('import.create')->withErrors(['file' => 'Import session expired. Upload again.']);
            }

            $mapping = $this->imports->validateMapping(
                $request->validate([
                    'mapping' => ['required', 'array'],
                    'mapping.*' => ['nullable', 'string'],
                ])['mapping']
            );

            // #region agent log
            $__dbgLog('P', 'mapping_validated', ['mapping_count' => count($mapping)]);
            // #endregion

            try {
                $rows = $this->imports->readRows(Storage::path($path));
            } catch (ValidationException $e) {
                Storage::delete($path);
                session()->forget(['import.path', 'import.columns', 'import.original_filename', 'import.mapping', 'import.prepared', 'import.prepared_path']);

                return redirect()->route('import.create')->withErrors($e->errors());
            }

            // #region agent log
            $__dbgLog('P', 'readRows_done', ['row_count' => $rows->count()]);
            // #endregion

            $prepared = $this->imports->prepareRows($rows, $mapping);
            unset($rows);

            // #region agent log
            $__dbgLog('P', 'prepareRows_done', ['prepared_count' => count($prepared)]);
            // #endregion

            $this->imports->rememberMapping($mapping, $request->user()?->id);

            $previousPrepared = session('import.prepared_path');
            if (is_string($previousPrepared)) {
                $this->imports->forgetPreparedRows($previousPrepared);
            }

            $preparedPath = $this->imports->storePreparedRows($prepared, $request->user()?->id);

            // #region agent log
            $__dbgLog('P', 'store_done', [
                'prepared_path' => $preparedPath,
                'store_bytes' => Storage::exists($preparedPath) ? (int) Storage::size($preparedPath) : 0,
            ]);
            // #endregion

            session([
                'import.mapping' => $mapping,
                'import.prepared_path' => $preparedPath,
            ]);
            session()->forget('import.prepared');

            // Count matches without cloning the full prepared list into a second large array.
            $duplicateCount = 0;
            $duplicatesPreview = [];
            $previewLimit = ImportService::DUPLICATES_PREVIEW_LIMIT;
            foreach ($prepared as $row) {
                if (($row['existing_id'] ?? null) === null) {
                    continue;
                }
                $duplicateCount++;
                if (count($duplicatesPreview) < $previewLimit) {
                    $duplicatesPreview[] = $row;
                }
            }
            $newCount = count($prepared) - $duplicateCount;
            $duplicatesHiddenCount = max(0, $duplicateCount - count($duplicatesPreview));
            unset($prepared);

            // #region agent log
            $__dbgLog('P', 'prepare_returning_view', [
                'duplicate_count' => $duplicateCount,
                'duplicates_preview' => count($duplicatesPreview),
                'duplicates_hidden' => $duplicatesHiddenCount,
                'new_count' => $newCount,
            ]);
            // #endregion

            return view('import.duplicates', [
                'duplicates' => $duplicatesPreview,
                'duplicateCount' => $duplicateCount,
                'duplicatesHiddenCount' => $duplicatesHiddenCount,
                'newCount' => $newCount,
                'totalCount' => $newCount + $duplicateCount,
                'fieldLabels' => ImportService::ASSET_FIELDS,
                'lists' => AssetList::query()->orderBy('type')->orderBy('name')->get(['id', 'name', 'type']),
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            // #region agent log
            $__dbgLog('P', 'prepare_exception', [
                'exception' => $e::class,
                'error' => Str::limit($e->getMessage(), 240),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            // #endregion

            report($e);

            return redirect()->route('import.create')->withErrors([
                'file' => 'Import prepare failed: '.Str::limit($e->getMessage(), 160),
            ]);
        }
    }

    public function process(Request $request): RedirectResponse
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $path = session('import.path');
        $preparedPath = session('import.prepared_path');
        $prepared = is_string($preparedPath)
            ? $this->imports->loadPreparedRows($preparedPath)
            : (is_array(session('import.prepared')) ? session('import.prepared') : null);

        if (! $path || ! is_array($prepared)) {
            return redirect()->route('import.create')->withErrors(['file' => 'Import session expired. Upload again.']);
        }

        $data = $request->validate([
            'actions' => ['nullable', 'array'],
            'actions.*' => ['in:update,replace,skip,create'],
            'bulk_action' => ['nullable', 'in:update,replace,skip,create'],
            'dry_run' => ['nullable', 'boolean'],
            'list_destination' => ['nullable', Rule::in(['none', 'existing', 'new'])],
            'asset_list_id' => [
                'nullable',
                Rule::requiredIf(fn () => $request->input('list_destination') === 'existing'),
                'exists:asset_lists,id',
            ],
            'new_list_name' => [
                'nullable',
                Rule::requiredIf(fn () => $request->input('list_destination') === 'new'),
                'string',
                'max:255',
            ],
            'new_list_type' => [
                'nullable',
                Rule::requiredIf(fn () => $request->input('list_destination') === 'new'),
                Rule::in(['inventory', 'event']),
            ],
            'include_updated_in_list' => ['nullable', 'boolean'],
        ]);

        $dryRun = $request->boolean('dry_run');
        $listDestination = $data['list_destination'] ?? 'none';
        $listOptions = [
            'destination' => $listDestination,
            'asset_list_id' => isset($data['asset_list_id']) ? (int) $data['asset_list_id'] : null,
            'new_list_name' => $data['new_list_name'] ?? null,
            'new_list_type' => $data['new_list_type'] ?? null,
            'include_updated' => $request->boolean('include_updated_in_list'),
        ];
        $actions = $data['actions'] ?? [];
        $bulkAction = $data['bulk_action'] ?? 'update';
        $userId = $request->user()?->id;
        $rowCount = count($prepared);

        // Large commits take minutes (audit log per row) and trip nginx 504s; finish after response.
        if (
            ! $dryRun
            && is_string($preparedPath)
            && $rowCount >= ImportService::BACKGROUND_COMMIT_THRESHOLD
        ) {
            $originalFilename = (string) (session('import.original_filename') ?: basename($path));

            ProcessAssetImportJob::dispatchAfterResponse(
                $path,
                $preparedPath,
                $originalFilename,
                $actions,
                $bulkAction,
                $listOptions,
                $userId,
            );

            session()->forget([
                'import.path',
                'import.columns',
                'import.original_filename',
                'import.mapping',
                'import.prepared',
                'import.prepared_path',
            ]);

            return redirect()->route('assets.index')->with(
                'status',
                sprintf(
                    'Import of %d rows is finishing in the background (usually 1–3 minutes). Refresh the assets list shortly.',
                    $rowCount,
                ),
            );
        }

        $counts = $this->imports->commit(
            $prepared,
            $actions,
            $dryRun,
            $bulkAction,
            $listOptions,
            $userId,
        );

        $archivedToLibrary = false;

        if (! $dryRun) {
            $archivedToLibrary = $this->archiveProcessedImport($path);
            Storage::delete($path);
            if (is_string($preparedPath)) {
                $this->imports->forgetPreparedRows($preparedPath);
            }
            session()->forget(['import.path', 'import.columns', 'import.original_filename', 'import.mapping', 'import.prepared', 'import.prepared_path']);
        }

        $message = sprintf(
            '%s: %d created, %d updated, %d replaced, %d skipped.',
            $dryRun ? 'Dry run' : 'Import complete',
            $counts['created'],
            $counts['updated'],
            $counts['replaced'],
            $counts['skipped'],
        );

        if ($listDestination !== 'none') {
            $message .= sprintf(
                $dryRun
                    ? ' %d would be added to a list.'
                    : ' %d added to list%s.',
                $counts['added_to_list'],
                $counts['list_name'] ? ' "'.$counts['list_name'].'"' : '',
            );
        }

        if (! $dryRun) {
            $message .= $archivedToLibrary
                ? ' Import file saved to the file library.'
                : ' Import file could not be saved to the file library.';
        }

        if (! $dryRun && $counts['list_id']) {
            return redirect()
                ->route('lists.show', $counts['list_id'])
                ->with('status', $message);
        }

        return redirect()->route('assets.index')->with('status', $message);
    }

    protected function archiveProcessedImport(string $path): bool
    {
        if (! Storage::exists($path)) {
            return false;
        }

        $originalFilename = (string) (session('import.original_filename') ?: basename($path));

        try {
            $this->attachments->storeFromAbsolutePath(
                Storage::path($path),
                $originalFilename,
                [
                    'title' => 'Asset import: '.$originalFilename,
                    'category' => FileCategory::Import->value,
                    'note' => 'Archived from a successful asset import.',
                ],
            );

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }
}
