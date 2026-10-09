<?php

namespace App\Http\Controllers;

use App\Enums\AssetStatus;
use App\Http\Requests\BulkUpdateAssetsRequest;
use App\Models\Asset;
use App\Models\AssetCustomFieldValue;
use App\Models\AssetList;
use App\Models\ItemType;
use App\Models\Location;
use App\Services\BulkAssetUpdateService;
use App\Support\AssetTableColumns;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssetController extends Controller
{
    public function index(Request $request): View
    {
        $term = $request->string('q')->toString() ?: null;

        $locationId = $request->integer('location_id') ?: null;

        $assets = Asset::query()
            ->with(['itemType', 'location', 'parent', 'customFieldValues'])
            ->whereNull('parent_id')
            ->search($term)
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->when($locationId, function ($query) use ($locationId) {
                $query->whereIn('location_id', Location::selfAndDescendantIds($locationId));
            })
            ->orderBySearchRelevance($term)
            ->paginate(50)
            ->withQueryString();

        return view('assets.index', [
            'assets' => $assets,
            'statuses' => AssetStatus::options(),
            'q' => $term ?? '',
            'locationId' => $locationId,
            'availableColumns' => AssetTableColumns::definitions(),
            'selectedColumns' => $request->user()->assetTableColumns(),
            'locations' => Location::with('parent.parent.parent')->orderBy('name')->get(),
            'parents' => Asset::whereNull('parent_id')->orderBy('name')->get(),
            'lists' => AssetList::orderBy('type')->orderBy('name')->get(),
        ]);
    }

    public function bulkUpdate(BulkUpdateAssetsRequest $request, BulkAssetUpdateService $bulkUpdate): RedirectResponse
    {
        $result = $bulkUpdate->handle($request->bulkPayload(), (int) $request->user()->id);

        return back()->with('status', $this->bulkStatusMessage($result));
    }

    /**
     * @param  array{updated: int, deleted: int, added_to_list: int, removed_from_list: int}  $result
     */
    protected function bulkStatusMessage(array $result): string
    {
        $parts = [];

        if ($result['deleted'] > 0) {
            $parts[] = $result['deleted'].' asset'.($result['deleted'] === 1 ? '' : 's').' deleted';
        }

        if ($result['updated'] > 0) {
            $parts[] = $result['updated'].' asset'.($result['updated'] === 1 ? '' : 's').' updated';
        }

        if ($result['added_to_list'] > 0) {
            $parts[] = $result['added_to_list'].' added to list';
        }

        if ($result['removed_from_list'] > 0) {
            $parts[] = $result['removed_from_list'].' removed from list';
        }

        return $parts === [] ? 'No changes applied.' : implode('. ', $parts).'.';
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

    public function show(Request $request, Asset $asset): View
    {
        $asset->load(['itemType.fieldSets.definitions', 'location.parent.parent', 'children.itemType', 'customFieldValues.definition', 'parent']);

        $tab = $request->string('tab')->toString() === 'history' ? 'history' : 'details';

        $auditLogs = $tab === 'history'
            ? $asset->auditLogs()->with('user')->paginate(25)->withQueryString()
            : null;

        return view('assets.show', [
            'asset' => $asset,
            'tab' => $tab,
            'auditLogs' => $auditLogs,
        ]);
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
