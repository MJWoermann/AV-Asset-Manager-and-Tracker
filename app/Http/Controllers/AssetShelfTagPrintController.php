<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\ShelfTagLayout;
use App\Services\ShelfTagRenderService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AssetShelfTagPrintController extends Controller
{
    public const SESSION_LAYOUT_KEY = 'shelf_tag_last_layout_id';

    public function show(Request $request, Asset $asset, ShelfTagRenderService $renderer): View
    {
        $layouts = ShelfTagLayout::query()->orderBy('name')->get();
        $layout = $this->resolveLayout($request, $layouts);

        $labels = [];
        if ($layout !== null) {
            $request->session()->put(self::SESSION_LAYOUT_KEY, $layout->id);
            $labels = $renderer->buildLabels(
                $layout,
                collect([$this->eagerLoadAsset($asset)]),
                withServerImages: false
            );
        }

        return view('assets.shelf-tag-print', [
            'asset' => $asset,
            'layouts' => $layouts,
            'layout' => $layout,
            'labels' => $labels,
        ]);
    }

    public function pdf(Request $request, Asset $asset, ShelfTagRenderService $renderer): SymfonyResponse
    {
        $layouts = ShelfTagLayout::query()->orderBy('name')->get();
        $layout = $this->resolveLayout($request, $layouts);

        abort_if($layout === null, 404);

        $request->session()->put(self::SESSION_LAYOUT_KEY, $layout->id);

        $labels = $renderer->buildLabels(
            $layout,
            collect([$this->eagerLoadAsset($asset)]),
            withServerImages: true
        );

        $pdf = Pdf::loadView('shelf-tags.print.pdf', [
            'layout' => $layout,
            'labels' => $labels,
        ])->setPaper([
            0,
            0,
            $this->mmToPoints($layout->displayWidthMm()),
            $this->mmToPoints($layout->displayHeightMm()),
        ]);

        $filename = 'label-'.$asset->id.'-'.now()->format('Ymd-His').'.pdf';

        return $pdf->download($filename);
    }

    /**
     * @param  Collection<int, ShelfTagLayout>  $layouts
     */
    protected function resolveLayout(Request $request, Collection $layouts): ?ShelfTagLayout
    {
        if ($layouts->isEmpty()) {
            return null;
        }

        $requestedId = $request->integer('layout') ?: null;
        if ($requestedId) {
            $layout = $layouts->firstWhere('id', $requestedId);
            if ($layout !== null) {
                return $layout;
            }
        }

        $sessionId = (int) $request->session()->get(self::SESSION_LAYOUT_KEY, 0);
        if ($sessionId > 0) {
            $layout = $layouts->firstWhere('id', $sessionId);
            if ($layout !== null) {
                return $layout;
            }
        }

        return $layouts->first();
    }

    protected function eagerLoadAsset(Asset $asset): Asset
    {
        return $asset->loadMissing(['location', 'itemType', 'customFieldValues.definition']);
    }

    protected function mmToPoints(float $mm): float
    {
        return $mm * 72 / 25.4;
    }
}
