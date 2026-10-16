<?php

namespace App\Http\Controllers\Api;

use App\Enums\FileCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\ImportUploadRequest;
use App\Models\AssetList;
use App\Services\FileAttachmentService;
use App\Services\ImportApiState;
use App\Services\ImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class ImportApiController extends Controller
{
    public function __construct(
        protected ImportService $imports,
        protected FileAttachmentService $attachments,
    ) {}

    public function status(Request $request): JsonResponse
    {
        $state = ImportApiState::get((int) $request->user()->id);

        return response()->json([
            'has_upload' => isset($state['path']) && Storage::exists((string) $state['path']),
            'has_prepared' => isset($state['prepared']) && is_array($state['prepared']),
            'original_filename' => $state['original_filename'] ?? null,
            'columns' => $state['columns'] ?? [],
        ]);
    }

    public function upload(ImportUploadRequest $request): JsonResponse
    {
        $uploaded = $request->file('file');
        $path = $uploaded->store('imports');
        $userId = (int) $request->user()->id;

        try {
            $absolutePath = Storage::path($path);
            $columns = $this->imports->readHeadingColumns($absolutePath);
            $columnExamples = $this->imports->readColumnExamples($absolutePath, $columns);
        } catch (ValidationException $e) {
            Storage::delete($path);

            throw $e;
        }

        ImportApiState::merge($userId, [
            'path' => $path,
            'columns' => $columns,
            'column_examples' => $columnExamples,
            'original_filename' => $uploaded->getClientOriginalName(),
        ]);
        ImportApiState::merge($userId, ['mapping' => null, 'prepared' => null]);

        $previous = $this->imports->recalledMapping($userId);
        $suggestedMappings = $this->imports->suggestMappings($columns, $previous);

        return response()->json([
            'columns' => $columns,
            'column_examples' => $columnExamples,
            'suggested_mappings' => $suggestedMappings,
            'fields' => ImportService::ASSET_FIELDS,
        ], 201);
    }

    public function prepare(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $state = ImportApiState::get($userId);
        $path = $state['path'] ?? null;
        $columns = $state['columns'] ?? [];

        if (! $path || ! Storage::exists($path)) {
            return response()->json(['message' => 'Import session expired. Upload again.'], 422);
        }

        $mapping = $this->imports->validateMapping(
            $request->validate([
                'mapping' => ['required', 'array'],
                'mapping.*' => ['nullable', 'string'],
            ])['mapping']
        );

        try {
            $rows = $this->imports->readRows(Storage::path($path));
        } catch (ValidationException $e) {
            Storage::delete($path);
            ImportApiState::forget($userId);

            throw $e;
        }

        $prepared = $this->imports->prepareRows($rows, $mapping);
        $this->imports->rememberMapping($mapping, $userId);

        ImportApiState::merge($userId, [
            'mapping' => $mapping,
            'prepared' => $prepared,
        ]);

        $duplicates = array_values(array_filter($prepared, fn (array $row) => $row['existing_id'] !== null));
        $newCount = count($prepared) - count($duplicates);

        return response()->json([
            'prepared_count' => count($prepared),
            'new_count' => $newCount,
            'duplicate_count' => count($duplicates),
            'duplicates' => $duplicates,
            'lists' => AssetList::query()->orderBy('type')->orderBy('name')->get(['id', 'name', 'type']),
        ]);
    }

    public function process(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $state = ImportApiState::get($userId);
        $path = $state['path'] ?? null;
        $prepared = $state['prepared'] ?? null;

        if (! $path || ! is_array($prepared)) {
            return response()->json(['message' => 'Import session expired. Upload again.'], 422);
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
        $counts = $this->imports->commit(
            $prepared,
            $data['actions'] ?? [],
            $dryRun,
            $data['bulk_action'] ?? 'update',
            [
                'destination' => $listDestination,
                'asset_list_id' => isset($data['asset_list_id']) ? (int) $data['asset_list_id'] : null,
                'new_list_name' => $data['new_list_name'] ?? null,
                'new_list_type' => $data['new_list_type'] ?? null,
                'include_updated' => $request->boolean('include_updated_in_list'),
            ],
            $userId,
        );

        if (! $dryRun) {
            $this->archiveProcessedImport($path, (string) ($state['original_filename'] ?? basename($path)));
            Storage::delete($path);
            ImportApiState::forget($userId);
        }

        return response()->json([
            'dry_run' => $dryRun,
            'counts' => $counts,
        ]);
    }

    protected function archiveProcessedImport(string $path, string $originalFilename): bool
    {
        if (! Storage::exists($path)) {
            return false;
        }

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
