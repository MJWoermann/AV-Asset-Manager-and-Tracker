<?php

namespace App\Http\Controllers;

use App\Imports\AssetImport;
use App\Models\ItemType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;

class ImportController extends Controller
{
    /** @var array<string, string> */
    public const ASSET_FIELDS = [
        'name' => 'Name',
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
        'device_sn' => 'Device SN',
        'ip_address' => 'IP Address',
        'mac_address' => 'MAC Address',
        'test_tag_expiry' => 'Test & Tag Expiry',
        'quantity' => 'Quantity',
        'notes' => 'Notes',
    ];

    public function create(): View
    {
        return view('import.create', [
            'itemTypes' => ItemType::orderBy('name')->get(),
            'fields' => self::ASSET_FIELDS,
        ]);
    }

    public function upload(Request $request): View|RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:25600', 'mimes:csv,xlsx,xls,txt'],
            'item_type_id' => ['required', 'exists:item_types,id'],
        ]);

        $path = $request->file('file')->store('imports');
        $headings = Excel::toArray(new HeadingRowImport, $request->file('file'));
        $columns = $headings[0][0] ?? [];

        session([
            'import.path' => $path,
            'import.item_type_id' => (int) $request->item_type_id,
            'import.columns' => $columns,
        ]);

        return view('import.map', [
            'columns' => $columns,
            'fields' => self::ASSET_FIELDS,
            'itemType' => ItemType::findOrFail($request->item_type_id),
        ]);
    }

    public function process(Request $request): RedirectResponse
    {
        $path = session('import.path');
        $itemTypeId = session('import.item_type_id');
        $columns = session('import.columns', []);

        if (! $path || ! $itemTypeId) {
            return redirect()->route('import.create')->withErrors(['file' => 'Import session expired. Upload again.']);
        }

        $mapping = $request->validate([
            'mapping' => ['required', 'array'],
            'mapping.*' => ['nullable', 'string'],
            'dry_run' => ['nullable', 'boolean'],
        ])['mapping'];

        $dryRun = $request->boolean('dry_run');
        $import = new AssetImport($itemTypeId, $mapping, $columns, $dryRun);
        Excel::import($import, Storage::path($path));

        if (! $dryRun) {
            Storage::delete($path);
            session()->forget(['import.path', 'import.item_type_id', 'import.columns']);
        }

        $message = $dryRun
            ? "Dry run: {$import->created} would be created, {$import->updated} updated, {$import->skipped} skipped."
            : "Import complete: {$import->created} created, {$import->updated} updated, {$import->skipped} skipped.";

        return redirect()->route('assets.index')->with('status', $message);
    }
}
