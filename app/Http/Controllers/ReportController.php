<?php

namespace App\Http\Controllers;

use App\Exports\ComparisonReportExport;
use App\Models\AssetList;
use App\Services\ListComparisonService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('reports.index');
    }

    public function compareForm(): View
    {
        return view('reports.compare', [
            'lists' => AssetList::query()->forCompareSelection()->get(),
            'result' => null,
            'source' => null,
            'target' => null,
        ]);
    }

    public function compare(Request $request, ListComparisonService $comparison): View|BinaryFileResponse|Response
    {
        $data = $request->validate([
            'scanned_list_id' => ['required', 'exists:asset_lists,id'],
            'inventory_list_id' => ['required', 'exists:asset_lists,id', 'different:scanned_list_id'],
            'export' => ['nullable', 'in:xlsx,csv,pdf'],
        ]);

        $source = AssetList::findOrFail($data['scanned_list_id']);
        $target = AssetList::findOrFail($data['inventory_list_id']);
        $result = $comparison->compare($source, $target);

        if (($data['export'] ?? null) === 'pdf') {
            return Pdf::loadView('reports.compare-pdf', [
                'result' => $result,
                'source' => $source,
                'target' => $target,
            ])->download($this->comparisonExportFilename($source, $target, 'pdf'));
        }

        if (! empty($data['export'])) {
            return Excel::download(
                new ComparisonReportExport($result, $source, $target),
                $this->comparisonExportFilename($source, $target, $data['export'])
            );
        }

        return view('reports.compare', [
            'lists' => AssetList::query()->forCompareSelection()->get(),
            'result' => $result,
            'source' => $source,
            'target' => $target,
        ]);
    }

    protected function comparisonExportFilename(AssetList $source, AssetList $target, string $format): string
    {
        $scanned = Str::slug($source->name) ?: 'scanned-list';
        $inventory = Str::slug($target->name) ?: 'inventory-list';

        return 'comparison-'.$scanned.'-vs-'.$inventory.'-'.now()->format('Ymd').'.'.$format;
    }
}
