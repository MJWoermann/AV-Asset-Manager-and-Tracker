<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSparePartRequest;
use App\Http\Requests\SupersedeSparePartRequest;
use App\Http\Requests\UpdateSparePartRequest;
use App\Models\Asset;
use App\Models\Location;
use App\Models\Manufacturer;
use App\Models\ManufacturerModel;
use App\Models\RepairProcedure;
use App\Models\ShelfTagLayout;
use App\Models\SparePart;
use App\Services\RepairService;
use App\Services\SparePartChartService;
use App\Services\SparePartService;
use App\Support\SparePartLuhn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SparePartController extends Controller
{
    public function index(Request $request): View
    {
        $includeEol = $request->boolean('include_eol');
        $belowMin = $request->boolean('below_min');
        $term = trim((string) $request->query('q', ''));
        $kind = $request->query('kind');
        $severity = $request->query('severity');
        $stockStatus = $request->query('stock_status');

        $query = SparePart::query()
            ->with(['manufacturer', 'manufacturerModel', 'location', 'supersededBy'])
            ->orderBy('code');

        if (! $includeEol) {
            $query->activeLifecycle();
        }

        if ($term !== '') {
            $query->search($term);
        }

        if (filled($kind)) {
            $query->where('kind', $kind);
        }

        if (filled($severity)) {
            $query->where('severity', $severity);
        }

        if (filled($stockStatus)) {
            $query->where('stock_status', $stockStatus);
        }

        if ($belowMin) {
            $query->whereNotNull('min_holding')
                ->whereColumn('quantity_on_hand', '<', 'min_holding');
        }

        $parts = $query->paginate(25)->withQueryString();

        return view('spares.index', [
            'parts' => $parts,
            'term' => $term,
            'kind' => $kind,
            'severity' => $severity,
            'stockStatus' => $stockStatus,
            'belowMin' => $belowMin,
            'includeEol' => $includeEol,
        ]);
    }

    public function create(): View
    {
        return view('spares.form', $this->formData(new SparePart([
            'code' => SparePartLuhn::generate(),
        ])));
    }

    public function store(StoreSparePartRequest $request, SparePartService $service): RedirectResponse
    {
        $data = $request->spareAttributes();

        $part = $service->create(
            $data,
            $data['compatible_model_ids'] ?? [],
            $data['compatible_asset_ids'] ?? [],
        );

        return redirect()->route('spares.show', $part)->with('status', 'Spare part created.');
    }

    public function storeFromAsset(Asset $asset, SparePartService $service): RedirectResponse
    {
        $result = $service->listAsset($asset);

        return redirect()
            ->route('spares.show', $result['spare'])
            ->with('status', $result['created']
                ? 'Asset listed in the spares register.'
                : 'This asset is already listed in the spares register.');
    }

    public function show(
        Request $request,
        SparePart $spare,
        SparePartService $movements,
        SparePartChartService $charts,
        RepairService $repairs,
    ): View {
        $spare->load([
            'manufacturer',
            'manufacturerModel',
            'location',
            'linkedAsset',
            'supersededBy',
            'supersedes',
            'compatibleModels.manufacturer',
            'compatibleAssets',
            'groupItems.group',
            'shelfTagLayout',
        ]);

        $movements->ensureBaseline($spare, $request->user());
        $spare->refresh();

        $movementHistory = $spare->movements()
            ->with(['user', 'againstAsset'])
            ->limit(50)
            ->get();

        $spare->load('files');
        $fileRows = $spare->files->map(fn ($file): array => [
            'file' => $file,
            'inherited' => false,
            'link_id' => (int) $file->pivot->id,
            'note' => $file->pivot->note,
        ])->all();

        $suggestedDeployed = $spare->manufacturer_model_id
            ? Asset::query()->where('manufacturer_model_id', $spare->manufacturer_model_id)->count()
            : null;

        $repairAssetOptions = $spare->compatibleAssets
            ->concat(
                $spare->manufacturer_model_id
                    ? Asset::query()->where('manufacturer_model_id', $spare->manufacturer_model_id)->orderBy('name')->limit(100)->get()
                    : collect()
            )
            ->unique('id')
            ->sortBy('name')
            ->values();

        if ($repairAssetOptions->isEmpty() && $spare->linkedAsset) {
            $repairAssetOptions = collect([$spare->linkedAsset]);
        }

        $suggestedProcedures = $repairAssetOptions->isNotEmpty()
            ? $repairs->suggestProcedures($repairAssetOptions->first(), $spare)
            : RepairProcedure::query()->where('spare_part_id', $spare->id)->orderBy('title')->get();

        return view('spares.show', [
            'spare' => $spare,
            'chartPayload' => $charts->build($spare),
            'movementHistory' => $movementHistory,
            'fileRows' => $fileRows,
            'movementAction' => $request->query('action'),
            'suggestedDeployed' => $suggestedDeployed,
            'assetOptions' => Asset::query()->orderBy('name')->limit(200)->get(['id', 'name']),
            'repairAssetOptions' => $repairAssetOptions,
            'suggestedProcedures' => $suggestedProcedures,
        ]);
    }

    public function showByCode(Request $request, string $code): View|RedirectResponse
    {
        $part = SparePart::query()->findByCode($code)->first();

        if (! $part) {
            abort(404);
        }

        return $this->show(
            $request,
            $part,
            app(SparePartService::class),
            app(SparePartChartService::class),
            app(RepairService::class),
        );
    }

    public function lookup(Request $request): JsonResponse
    {
        $code = trim((string) $request->query('code', ''));
        if ($code === '') {
            return response()->json(['found' => false]);
        }

        $part = SparePart::query()->findByCode($code)->first();
        if (! $part && SparePartLuhn::isValid(preg_replace('/\D/', '', $code) ?? '')) {
            $part = SparePart::query()->findByCode(preg_replace('/\D/', '', $code) ?? '')->first();
        }

        if (! $part) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found' => true,
            'id' => $part->id,
            'code' => $part->code,
            'component' => $part->component,
            'url' => route('spares.show', $part),
        ]);
    }

    public function edit(SparePart $spare): View
    {
        $spare->load(['compatibleModels', 'compatibleAssets']);

        return view('spares.form', $this->formData($spare));
    }

    public function update(UpdateSparePartRequest $request, SparePart $spare, SparePartService $service): RedirectResponse
    {
        $data = $request->spareAttributes();
        $service->update(
            $spare,
            $data,
            $data['compatible_model_ids'] ?? [],
            $data['compatible_asset_ids'] ?? [],
        );

        return redirect()->route('spares.show', $spare)->with('status', 'Spare part updated.');
    }

    public function destroy(SparePart $spare): RedirectResponse
    {
        abort_unless(auth()->user()?->can('spares:manage'), 403);

        if ($spare->movements()->exists()) {
            return back()->withErrors(['spare' => 'Cannot delete a spare with movement history. Mark it obsolete instead.']);
        }

        $spare->delete();

        return redirect()->route('spares.index')->with('status', 'Spare part deleted.');
    }

    public function supersede(SupersedeSparePartRequest $request, SparePart $spare, SparePartService $service): RedirectResponse
    {
        $successor = SparePart::query()->findOrFail($request->validated('superseded_by_id'));
        $service->supersede($spare, $successor, $request->user());

        return redirect()->route('spares.show', $spare)->with('status', 'Supersession recorded.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(SparePart $spare): array
    {
        $manufacturers = Manufacturer::query()
            ->with(['models' => fn ($q) => $q->orderBy('name')])
            ->orderBy('name')
            ->get();

        $models = ManufacturerModel::query()->with('manufacturer')->orderBy('name')->get();

        return [
            'spare' => $spare,
            'manufacturers' => $manufacturers,
            'models' => $models,
            'modelsByManufacturer' => $manufacturers
                ->mapWithKeys(fn (Manufacturer $manufacturer): array => [
                    (string) $manufacturer->id => $manufacturer->models
                        ->map(fn (ManufacturerModel $model): array => [
                            'value' => (string) $model->id,
                            'label' => $model->name,
                        ])
                        ->values()
                        ->all(),
                ])
                ->all(),
            'locations' => Location::query()->orderBy('name')->get(),
            'layouts' => ShelfTagLayout::query()->orderBy('name')->get(),
            'successorOptions' => SparePart::query()
                ->when($spare->exists, fn ($q) => $q->where('id', '!=', $spare->id))
                ->activeLifecycle()
                ->orderBy('code')
                ->get(['id', 'code', 'component']),
        ];
    }
}
