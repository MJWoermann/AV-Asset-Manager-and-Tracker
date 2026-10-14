<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShelfTagPrintRequest;
use App\Models\Asset;
use App\Models\AssetList;
use App\Models\ShelfTagLayout;
use App\Services\ShelfTagRenderService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ShelfTagPrintController extends Controller
{
    public const SESSION_KEY = 'shelf_tag_print_job';

    public function create(Request $request): View
    {
        $lists = AssetList::query()->orderBy('type')->orderBy('name')->get();
        $layouts = ShelfTagLayout::query()->orderBy('name')->get();

        $selectedIds = collect($request->query('asset_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();

        return view('shelf-tags.print.create', [
            'lists' => $lists,
            'layouts' => $layouts,
            'selectedListId' => $request->integer('asset_list_id') ?: null,
            'selectedLayoutId' => $request->integer('shelf_tag_layout_id') ?: null,
            'mode' => $selectedIds !== [] ? 'selected' : 'all',
            'selectedAssetIds' => $selectedIds,
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

        return redirect()->route('shelf-tags.print.show');
    }

    public function show(Request $request, ShelfTagRenderService $renderer): View|RedirectResponse
    {
        $job = $this->jobFromSession($request);
        if ($job === null) {
            return redirect()
                ->route('shelf-tags.print.create')
                ->withErrors(['shelf_tag_layout_id' => 'Choose a layout and list to preview shelf tags.']);
        }

        [$layout, $list, $assets] = $this->loadJob($job, $renderer);
        $labels = $renderer->buildLabels($layout, $assets, withServerImages: false);

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
                ->route('shelf-tags.print.create')
                ->withErrors(['shelf_tag_layout_id' => 'Choose a layout and list before downloading PDF.']);
        }

        [$layout, $list, $assets] = $this->loadJob($job, $renderer);
        $labels = $renderer->buildLabels($layout, $assets, withServerImages: true);

        $pdf = Pdf::loadView('shelf-tags.print.pdf', [
            'layout' => $layout,
            'labels' => $labels,
        ])->setPaper([0, 0, $this->mmToPoints((float) $layout->width_mm), $this->mmToPoints((float) $layout->height_mm)]);

        $filename = 'shelf-tags-'.$list->id.'-'.now()->format('Ymd-His').'.pdf';

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
