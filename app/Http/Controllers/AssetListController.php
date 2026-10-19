<?php

namespace App\Http\Controllers;

use App\Enums\AssetStatus;
use App\Http\Requests\BulkUpdateAssetListsRequest;
use App\Models\AssetList;
use App\Models\Department;
use App\Models\ItemType;
use App\Models\Location;
use App\Models\Manufacturer;
use App\Models\ManufacturerModel;
use App\Services\BulkAssetListUpdateService;
use App\Support\AssetBrowseFilters;
use App\Support\AssetTableColumns;
use App\Support\PageSize;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssetListController extends Controller
{
    public function index(Request $request): View
    {
        $lists = AssetList::withCount('items')->orderBy('type')->orderBy('name')->get();

        return view('lists.index', [
            'lists' => $lists,
            'departments' => $request->user()?->hasRole('admin')
                ? Department::query()->where('is_active', true)->orderBy('name')->get(['id', 'name'])
                : collect(),
        ]);
    }

    public function bulkUpdate(BulkUpdateAssetListsRequest $request, BulkAssetListUpdateService $bulkUpdate): RedirectResponse
    {
        $result = $bulkUpdate->handle($request->bulkPayload());

        return redirect()->route('lists.index')->with('status', $this->bulkStatusMessage($result));
    }

    /**
     * @param  array{updated: int}  $result
     */
    protected function bulkStatusMessage(array $result): string
    {
        $updated = (int) ($result['updated'] ?? 0);

        if ($updated === 0) {
            return 'No changes applied.';
        }

        return $updated.' list'.($updated === 1 ? '' : 's').' updated.';
    }

    public function create(): View
    {
        return view('lists.form', ['list' => new AssetList(['type' => 'event'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:inventory,event'],
            'description' => ['nullable', 'string'],
        ]);

        $list = AssetList::create($data + ['created_by' => auth()->id()]);

        return redirect()->route('lists.show', $list)->with('status', 'List created.');
    }

    public function show(Request $request, AssetList $list): View|RedirectResponse
    {
        $selectedColumns = $request->user()->assetTableColumns();

        if (AssetBrowseFilters::hasFilledHiddenColumnFilters($request, $selectedColumns)) {
            return redirect()->to(
                AssetBrowseFilters::urlWithoutHiddenColumnFilters($request->fullUrl(), $selectedColumns)
            );
        }

        $term = AssetBrowseFilters::term($request);

        $items = $list->items()
            ->with([
                'asset.itemType',
                'asset.location',
                'asset.parent',
                'asset.manufacturer',
                'asset.manufacturerModel',
                'asset.customFieldValues',
            ])
            ->whereHas(
                'asset',
                fn ($query) => AssetBrowseFilters::applyToAssetQuery(
                    $query,
                    $request,
                    includeQuantity: false,
                    selectedColumns: $selectedColumns,
                )
            );

        AssetBrowseFilters::applyToListItemQuery($items, $request, $selectedColumns);

        $items = PageSize::paginate(
            $items
                ->join('assets', 'assets.id', '=', 'asset_list_items.asset_id')
                ->orderByRaw('COALESCE(assets.parent_id, assets.id)')
                ->orderBy('assets.parent_id')
                ->orderBy('assets.name')
                ->select('asset_list_items.*'),
            $request,
        );

        return view('lists.show', [
            'list' => $list,
            'items' => $items,
            'q' => $term ?? '',
            'availableColumns' => AssetTableColumns::definitions(),
            'selectedColumns' => $selectedColumns,
            'statuses' => AssetStatus::options(),
            'locations' => Location::with('parent.parent.parent.parent')->orderBy('name')->get(),
            'itemTypes' => ItemType::orderBy('name')->get(),
            'manufacturers' => Manufacturer::query()->orderBy('name')->get(['id', 'name']),
            'manufacturerModels' => ManufacturerModel::query()
                ->with('manufacturer:id,name')
                ->orderBy('name')
                ->get(['id', 'name', 'manufacturer_id']),
            'parents' => collect(),
            'lists' => AssetList::orderBy('type')->orderBy('name')->get(),
            'departments' => $request->user()?->hasRole('admin')
                ? Department::query()->where('is_active', true)->orderBy('name')->get(['id', 'name'])
                : collect(),
            'departmentColours' => $request->user()?->departmentColoursForBrowse() ?? [],
        ]);
    }

    public function edit(AssetList $list): View
    {
        return view('lists.form', compact('list'));
    }

    public function update(Request $request, AssetList $list): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:inventory,event'],
            'description' => ['nullable', 'string'],
        ]);
        $list->update($data);

        return redirect()->route('lists.show', $list)->with('status', 'List updated.');
    }

    public function destroy(AssetList $list): RedirectResponse
    {
        abort_unless(auth()->user()->hasAnyRole(['admin', 'inventory_manager']), 403);
        $list->delete();

        return redirect()->route('lists.index')->with('status', 'List deleted.');
    }
}
