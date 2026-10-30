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

        // #region agent log
        $barcodeProbe = [];
        $textProbe = [];
        foreach ($labels as $labelIndex => $label) {
            foreach ($label['elements'] as $element) {
                $type = (string) ($element['type'] ?? '');
                if ($type === 'barcode' && count($barcodeProbe) < 3) {
                    $uri = (string) ($element['image_data_uri'] ?? '');
                    $png = null;
                    $bottomInk = null;
                    $kind = str_starts_with($uri, 'data:image/svg+xml;base64,') ? 'svg' : (str_starts_with($uri, 'data:image/png;base64,') ? 'png' : null);
                    $hasCaption = ! empty($element['props']['showText']);
                    if (str_starts_with($uri, 'data:image/png;base64,')) {
                        $raw = base64_decode(substr($uri, strlen('data:image/png;base64,')), true);
                        if ($raw !== false) {
                            $info = @getimagesizefromstring($raw);
                            $png = $info ? ['w' => $info[0], 'h' => $info[1]] : null;
                            $image = @imagecreatefromstring($raw);
                            if ($image !== false) {
                                $width = imagesx($image);
                                $height = imagesy($image);
                                $hits = 0;
                                $samples = 0;
                                for ($x = 0; $x < $width; $x += 4) {
                                    $samples++;
                                    $rgb = imagecolorat($image, $x, $height - 1);
                                    if (($rgb & 0xFF) < 128) {
                                        $hits++;
                                    }
                                }
                                $bottomInk = $samples > 0 ? round($hits / $samples, 3) : null;
                                imagedestroy($image);
                            }
                        }
                    }
                    $barcodeProbe[] = [
                        'index' => $labelIndex,
                        'showText' => ! empty($element['props']['showText']),
                        'hasImage' => $uri !== '',
                        'kind' => $kind,
                        'hasCaption' => $hasCaption,
                        'png' => $png,
                        'bottomInk' => $bottomInk,
                        'wMm' => $element['width'] ?? null,
                        'hMm' => $element['height'] ?? null,
                    ];
                }
                if (in_array($type, ['text', 'field'], true) && $labelIndex === 0 && count($textProbe) < 6) {
                    $textProbe[] = [
                        'type' => $type,
                        'fontSize' => $element['props']['fontSize'] ?? null,
                        'unit' => 'pt',
                    ];
                }
            }
        }
        file_put_contents(base_path('debug-c42c70.log'), json_encode([
            'sessionId' => 'c42c70',
            'runId' => 'post-fix',
            'hypothesisId' => 'D',
            'location' => 'ShelfTagPrintController.php:pdf',
            'message' => 'pdf barcode and text probe',
            'data' => [
                'dpi' => config('dompdf.options.dpi'),
                'paperMm' => [$paperW, $paperH],
                'labelCount' => count($labels),
                'barcodeProbe' => $barcodeProbe,
                'textProbe' => $textProbe,
                'pdfFont' => 'DejaVu Sans',
            ],
            'timestamp' => (int) (microtime(true) * 1000),
        ]).PHP_EOL, FILE_APPEND);
        // #endregion

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
