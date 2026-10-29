<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShelfTagPrintRequest;
use App\Models\Asset;
use App\Models\AssetList;
use App\Models\ShelfTagLayout;
use App\Services\ShelfTagRenderService;
use App\Support\AssetBrowseFilters;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ShelfTagPrintController extends Controller
{
    public const SESSION_KEY = 'shelf_tag_print_job';

    /** Max assets rendered on the print setup checklist. */
    public const SELECTION_DISPLAY_CAP = 500;

    public function create(Request $request): View
    {
        $lists = AssetList::query()->orderBy('type')->orderBy('name')->get();
        $layouts = ShelfTagLayout::query()->orderBy('name')->get();

        $selectedIds = collect(old('asset_ids', $request->query('asset_ids', [])))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();

        $selectedListId = (int) (old('asset_list_id') ?: $request->integer('asset_list_id') ?: 0) ?: null;
        $selectedLayoutId = (int) (old('shelf_tag_layout_id') ?: $request->integer('shelf_tag_layout_id') ?: 0) ?: null;
        $term = AssetBrowseFilters::term($request);
        $listAssets = collect();
        $listAssetTotal = 0;
        $listAssetTruncated = false;

        if ($selectedListId !== null) {
            $list = AssetList::query()->find($selectedListId);

            if ($list !== null) {
                $query = Asset::query()
                    ->whereIn(
                        'id',
                        $list->items()->whereNotNull('asset_id')->select('asset_id')
                    )
                    ->with(['itemType', 'location', 'manufacturer', 'manufacturerModel']);

                AssetBrowseFilters::applyToAssetQuery($query, $request, includeQuantity: false);

                $listAssetTotal = (clone $query)->count();
                $listAssets = (clone $query)
                    ->orderBy('name')
                    ->limit(self::SELECTION_DISPLAY_CAP)
                    ->get();
                $listAssetTruncated = $listAssetTotal > $listAssets->count();
            }
        }

        $displayedIds = $listAssets->pluck('id')->map(fn ($id) => (int) $id)->all();

        if ($selectedIds !== []) {
            $preselectedIds = array_values(array_intersect($selectedIds, $displayedIds));
        } elseif (
            $listAssets->isNotEmpty()
            && $listAssetTotal <= ShelfTagRenderService::MAX_BATCH
            && ! AssetBrowseFilters::hasActiveFilters($request)
        ) {
            $preselectedIds = $displayedIds;
        } else {
            $preselectedIds = [];
        }

        return view('shelf-tags.index', [
            'lists' => $lists,
            'layouts' => $layouts,
            'selectedListId' => $selectedListId,
            'selectedLayoutId' => $selectedLayoutId,
            'listAssets' => $listAssets,
            'listAssetTotal' => $listAssetTotal,
            'listAssetTruncated' => $listAssetTruncated,
            'preselectedIds' => $preselectedIds,
            'q' => $term ?? '',
            'maxBatch' => ShelfTagRenderService::MAX_BATCH,
        ]);
    }

    public function preview(ShelfTagPrintRequest $request, ShelfTagRenderService $renderer): RedirectResponse
    {
        $layout = ShelfTagLayout::query()->findOrFail($request->validated('shelf_tag_layout_id'));
        $list = AssetList::query()->findOrFail($request->validated('asset_list_id'));
        $assets = $renderer->resolveAssets($list, $request->selectedAssetIds());

        if ($assets->isEmpty()) {
            return back()->withErrors([
                'asset_list_id' => 'This list has no assets to print.',
            ])->withInput();
        }

        $request->session()->put(self::SESSION_KEY, [
            'layout_id' => $layout->id,
            'list_id' => $list->id,
            'asset_ids' => $assets->pluck('id')->all(),
            'mode' => $request->validated('mode'),
        ]);

        // #region agent log
        file_put_contents(base_path('debug-5e9f10.log'), json_encode(['sessionId' => '5e9f10', 'runId' => 'pre-fix', 'hypothesisId' => 'E', 'location' => 'ShelfTagPrintController.php:preview', 'message' => 'stored print job', 'data' => ['layout_id' => $layout->id, 'paper_preset' => $layout->paper_preset, 'width_mm' => (float) $layout->width_mm, 'height_mm' => (float) $layout->height_mm, 'orientation' => $layout->orientation, 'length_mode' => $layout->length_mode, 'display_mm' => [$layout->displayWidthMm(), $layout->displayHeightMm()], 'asset_count' => $assets->count(), 'mode' => $request->validated('mode')], 'timestamp' => (int) round(microtime(true) * 1000)])."\n", FILE_APPEND);
        // #endregion

        return redirect()->route('labels.print.show');
    }

    public function show(Request $request, ShelfTagRenderService $renderer): View|RedirectResponse
    {
        $job = $this->jobFromSession($request);
        if ($job === null) {
            return redirect()
                ->route('labels.index')
                ->withErrors(['shelf_tag_layout_id' => 'Choose a layout and list to preview labels.']);
        }

        [$layout, $list, $assets] = $this->loadJob($job, $renderer);
        $labels = $renderer->buildLabels($layout, $assets, withServerImages: false);

        // #region agent log
        file_put_contents(base_path('debug-5e9f10.log'), json_encode(['sessionId' => '5e9f10', 'runId' => 'pre-fix', 'hypothesisId' => 'B,C,E', 'location' => 'ShelfTagPrintController.php:show', 'message' => 'preview page size', 'data' => ['layout_id' => $layout->id, 'paper_preset' => $layout->paper_preset, 'width_mm' => (float) $layout->width_mm, 'height_mm' => (float) $layout->height_mm, 'orientation' => $layout->orientation, 'length_mode' => $layout->length_mode, 'display_mm' => [$layout->displayWidthMm(), $layout->displayHeightMm()], 'label_count' => count($labels), 'page_css' => $layout->displayWidthMm().'mm '.$layout->displayHeightMm().'mm'], 'timestamp' => (int) round(microtime(true) * 1000)])."\n", FILE_APPEND);
        // #endregion

        return view('shelf-tags.print.preview', [
            'layout' => $layout,
            'list' => $list,
            'labels' => $labels,
            'count' => count($labels),
            'previewLimit' => 6,
        ]);
    }

    public function pdf(Request $request, ShelfTagRenderService $renderer): SymfonyResponse|RedirectResponse
    {
        $job = $this->jobFromSession($request);
        if ($job === null) {
            return redirect()
                ->route('labels.index')
                ->withErrors(['shelf_tag_layout_id' => 'Choose a layout and list before downloading PDF.']);
        }

        [$layout, $list, $assets] = $this->loadJob($job, $renderer);
        $labels = $renderer->buildLabels($layout, $assets, withServerImages: true);

        $paperW = $layout->displayWidthMm();
        $paperH = $layout->displayHeightMm();

        $pdf = Pdf::loadView('shelf-tags.print.pdf', [
            'layout' => $layout,
            'labels' => $labels,
        ])->setPaper([0, 0, $this->mmToPoints($paperW), $this->mmToPoints($paperH)]);

        $filename = 'labels-'.$list->id.'-'.now()->format('Ymd-His').'.pdf';

        return $pdf->download($filename);
    }

    /**
     * @return array{layout_id: int, list_id: int, asset_ids: list<int>, mode: string}|null
     */
    protected function jobFromSession(Request $request): ?array
    {
        $job = $request->session()->get(self::SESSION_KEY);
        if (! is_array($job) || empty($job['layout_id']) || empty($job['list_id']) || empty($job['asset_ids'])) {
            return null;
        }

        return [
            'layout_id' => (int) $job['layout_id'],
            'list_id' => (int) $job['list_id'],
            'asset_ids' => array_map('intval', $job['asset_ids']),
            'mode' => (string) ($job['mode'] ?? 'selected'),
        ];
    }

    /**
     * @param  array{layout_id: int, list_id: int, asset_ids: list<int>, mode: string}  $job
     * @return array{0: ShelfTagLayout, 1: AssetList, 2: Collection<int, Asset>}
     */
    protected function loadJob(array $job, ShelfTagRenderService $renderer): array
    {
        $layout = ShelfTagLayout::query()->findOrFail($job['layout_id']);
        $list = AssetList::query()->findOrFail($job['list_id']);
        $assets = $renderer->resolveAssets($list, $job['asset_ids']);

        return [$layout, $list, $assets];
    }

    protected function mmToPoints(float $mm): float
    {
        return $mm * 72 / 25.4;
    }
}
