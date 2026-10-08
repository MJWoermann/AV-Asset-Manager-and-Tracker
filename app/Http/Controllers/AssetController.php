<?php

namespace App\Http\Controllers;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetCustomFieldValue;
use App\Models\ItemType;
use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssetController extends Controller
{
    public function index(Request $request): View
    {
        $assets = Asset::query()
            ->with(['itemType', 'location', 'parent'])
            ->whereNull('parent_id')
            ->search($request->string('q')->toString() ?: null)
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->orderBy('name')
            ->paginate(50)
            ->withQueryString();

        return view('assets.index', [
            'assets' => $assets,
            'statuses' => AssetStatus::options(),
            'q' => $request->string('q')->toString(),
        ]);
    }

    public function create(): View
    {
        return view('assets.form', [
            'asset' => new Asset(['status' => AssetStatus::Available]),
            'itemTypes' => ItemType::orderBy('name')->get(),
            'locations' => Location::orderBy('name')->get(),
            'parents' => Asset::whereNull('parent_id')->orderBy('name')->get(),
            'statuses' => AssetStatus::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $asset = Asset::create($data);
        $this->syncCustomFields($asset, $request);

        return redirect()->route('assets.show', $asset)->with('status', 'Asset created.');
    }

    public function show(Asset $asset): View
    {
        $asset->load(['itemType.fieldSets.definitions', 'location.parent.parent', 'children.itemType', 'customFieldValues.definition', 'parent']);

        return view('assets.show', compact('asset'));
    }

    public function edit(Asset $asset): View
    {
        $asset->load(['itemType.fieldSets.definitions', 'customFieldValues']);

        return view('assets.form', [
            'asset' => $asset,
            'itemTypes' => ItemType::orderBy('name')->get(),
            'locations' => Location::orderBy('name')->get(),
            'parents' => Asset::whereNull('parent_id')->where('id', '!=', $asset->id)->orderBy('name')->get(),
            'statuses' => AssetStatus::options(),
        ]);
    }

    public function update(Request $request, Asset $asset): RedirectResponse
    {
        $asset->update($this->validated($request));
        $this->syncCustomFields($asset, $request);

        return redirect()->route('assets.show', $asset)->with('status', 'Asset updated.');
    }

    public function destroy(Asset $asset): RedirectResponse
    {
        abort_unless(auth()->user()->hasAnyRole(['admin', 'inventory_manager']), 403);
        $asset->delete();

        return redirect()->route('assets.index')->with('status', 'Asset deleted.');
    }

    /** @return array<string, mixed> */
    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'item_type_id' => ['required', 'exists:item_types,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'parent_id' => ['nullable', 'exists:assets,id'],
            'status' => ['required', 'in:'.implode(',', array_keys(AssetStatus::options()))],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['nullable', 'date'],
            'warranty_expiry' => ['nullable', 'date'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'replacement_cost' => ['nullable', 'numeric', 'min:0'],
            'fmi_ast' => ['nullable', 'string', 'max:255'],
            'tp_barcode' => ['nullable', 'string', 'max:255'],
            'rig_tag' => ['nullable', 'string', 'max:255'],
            'device_sn' => ['nullable', 'string', 'max:255'],
            'ip_address' => ['nullable', 'string', 'max:45'],
            'mac_address' => ['nullable', 'string', 'max:32'],
            'test_tag_expiry' => ['nullable', 'date'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    protected function syncCustomFields(Asset $asset, Request $request): void
    {
        $values = $request->input('custom_fields', []);
        if (! is_array($values)) {
            return;
        }

        foreach ($values as $definitionId => $value) {
            if ($value === null || $value === '') {
                AssetCustomFieldValue::where('asset_id', $asset->id)
                    ->where('custom_field_definition_id', $definitionId)
                    ->delete();

                continue;
            }

            AssetCustomFieldValue::updateOrCreate(
                [
                    'asset_id' => $asset->id,
                    'custom_field_definition_id' => $definitionId,
                ],
                ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value]
            );
        }
    }
}
