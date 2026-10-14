<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportUploadRequest;
use App\Services\ImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ImportController extends Controller
{
    public function __construct(protected ImportService $imports) {}

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
            $columns = $this->imports->readHeadingColumns(Storage::path($path));
        } catch (ValidationException $e) {
            Storage::delete($path);

            throw $e;
        }

        session([
            'import.path' => $path,
            'import.columns' => $columns,
        ]);
        session()->forget(['import.mapping', 'import.prepared']);

        $previous = $this->imports->recalledMapping($request->user()?->id);
        $suggestedMappings = $this->imports->suggestMappings($columns, $previous);

        return view('import.map', [
            'columns' => $columns,
            'fields' => ImportService::ASSET_FIELDS,
            'createNewField' => ImportService::CREATE_NEW_FIELD,
            'suggestedMappings' => $suggestedMappings,
            'usedRememberedMappings' => $previous !== [],
        ]);
    }

    public function prepare(Request $request): View|RedirectResponse
    {
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

        try {
            $rows = $this->imports->readRows(Storage::path($path));
        } catch (ValidationException $e) {
            Storage::delete($path);
            session()->forget(['import.path', 'import.columns', 'import.mapping', 'import.prepared']);

            return redirect()->route('import.create')->withErrors($e->errors());
        }

        $prepared = $this->imports->prepareRows($rows, $mapping);

        $this->imports->rememberMapping($mapping, $request->user()?->id);

        session([
            'import.mapping' => $mapping,
            'import.prepared' => $prepared,
        ]);

        $duplicates = array_values(array_filter($prepared, fn (array $row) => $row['existing_id'] !== null));
        $newCount = count($prepared) - count($duplicates);

        return view('import.duplicates', [
            'duplicates' => $duplicates,
            'newCount' => $newCount,
            'totalCount' => count($prepared),
            'fieldLabels' => ImportService::ASSET_FIELDS,
        ]);
    }

    public function process(Request $request): RedirectResponse
    {
        $path = session('import.path');
        $prepared = session('import.prepared');

        if (! $path || ! is_array($prepared)) {
            return redirect()->route('import.create')->withErrors(['file' => 'Import session expired. Upload again.']);
        }

        $data = $request->validate([
            'actions' => ['nullable', 'array'],
            'actions.*' => ['in:update,replace,skip,create'],
            'bulk_action' => ['nullable', 'in:update,replace,skip,create'],
            'dry_run' => ['nullable', 'boolean'],
        ]);

        $dryRun = $request->boolean('dry_run');
        $counts = $this->imports->commit(
            $prepared,
            $data['actions'] ?? [],
            $dryRun,
            $data['bulk_action'] ?? 'update',
        );

        if (! $dryRun) {
            Storage::delete($path);
            session()->forget(['import.path', 'import.columns', 'import.mapping', 'import.prepared']);
        }

        $message = sprintf(
            '%s: %d created, %d updated, %d replaced, %d skipped.',
            $dryRun ? 'Dry run' : 'Import complete',
            $counts['created'],
            $counts['updated'],
            $counts['replaced'],
            $counts['skipped'],
        );

        return redirect()->route('assets.index')->with('status', $message);
    }
}
