<?php

namespace App\Http\Controllers;

use App\Imports\AssetImport;
use App\Services\ImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;

class ImportController extends Controller
{
    public function __construct(protected ImportService $imports) {}

    public function create(): View
    {
        return view('import.create');
    }

    public function upload(Request $request): View|RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:25600', 'mimes:csv,xlsx,xls,txt'],
        ]);

        $path = $request->file('file')->store('imports');
        $headings = Excel::toArray(new HeadingRowImport, $request->file('file'));
        $columns = $headings[0][0] ?? [];

        session([
            'import.path' => $path,
            'import.columns' => $columns,
        ]);
        session()->forget(['import.mapping', 'import.prepared']);

        return view('import.map', [
            'columns' => $columns,
            'fields' => ImportService::ASSET_FIELDS,
            'createNewField' => ImportService::CREATE_NEW_FIELD,
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

        $reader = new AssetImport;
        Excel::import($reader, Storage::path($path));
        $prepared = $this->imports->prepareRows($reader->rows, $mapping);

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
            'actions.*' => ['in:update,replace,skip'],
            'bulk_action' => ['nullable', 'in:update,replace,skip'],
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
