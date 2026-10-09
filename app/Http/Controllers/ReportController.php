<?php

namespace App\Http\Controllers;

use App\Exports\ComparisonReportExport;
use App\Models\AssetList;
use App\Services\ListComparisonService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function compareForm(): View
    {
        return view('reports.compare', [
            'lists' => AssetList::orderBy('type')->orderBy('name')->get(),
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
            $filename = 'comparison-'.$source->id.'-vs-'.$target->id.'.pdf';

            return Pdf::loadView('reports.compare-pdf', [
                'result' => $result,
                'source' => $source,
                'target' => $target,
            ])->download($filename);
        }

        if (! empty($data['export'])) {
            $filename = 'comparison-'.$source->id.'-vs-'.$target->id.'.'.$data['export'];

            return Excel::download(
                new ComparisonReportExport($result, $source, $target),
                $filename
            );
        }

        return view('reports.compare', [
            'lists' => AssetList::orderBy('type')->orderBy('name')->get(),
            'result' => $result,
            'source' => $source,
            'target' => $target,
        ]);
    }
}
