<?php

namespace App\Http\Controllers;

use App\Models\ShelfTagLayout;
use App\Models\SparePart;
use App\Services\SpareTagRenderService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class SparePartShelfTagPrintController extends Controller
{
    public function label(Request $request, SparePart $spare, SpareTagRenderService $renderer): View|RedirectResponse
    {
        $layout = $spare->shelfTagLayout
            ?? ShelfTagLayout::query()->orderBy('name')->first();

        if ($layout === null) {
            return redirect()->route('spares.show', $spare)
                ->withErrors(['label' => 'No shelf tag layout is available. Create one under Reports → Labels.']);
        }

        $labels = $renderer->buildLabels($layout, collect([$spare]), withServerImages: true);

        return view('shelf-tags.print', [
            'layout' => $layout,
            'labels' => $labels,
            'entityLabel' => 'spare',
        ]);
    }

    public function batch(Request $request, SpareTagRenderService $renderer): View|RedirectResponse|SymfonyResponse
    {
        abort_unless($request->user()?->can('labels:read'), 403);

        $data = $request->validate([
            'shelf_tag_layout_id' => ['required', 'integer', 'exists:shelf_tag_layouts,id'],
            'spare_ids' => ['required', 'array', 'min:1'],
            'spare_ids.*' => ['integer', 'exists:spare_parts,id'],
            'format' => ['nullable', 'in:html,pdf'],
        ]);

        $layout = ShelfTagLayout::query()->findOrFail($data['shelf_tag_layout_id']);
        $spares = $renderer->resolveSpares($data['spare_ids']);
        $labels = $renderer->buildLabels($layout, $spares, withServerImages: true);

        if (($data['format'] ?? 'html') === 'pdf') {
            $pdf = Pdf::loadView('shelf-tags.print-pdf', [
                'layout' => $layout,
                'labels' => $labels,
            ]);

            return $pdf->download('spare-labels.pdf');
        }

        return view('shelf-tags.print', [
            'layout' => $layout,
            'labels' => $labels,
            'entityLabel' => 'spare',
        ]);
    }
}
