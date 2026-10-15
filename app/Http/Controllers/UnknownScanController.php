<?php

namespace App\Http\Controllers;

use App\Exports\UnknownScansExport;
use App\Models\UnknownScan;
use App\Services\ScanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UnknownScanController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        if (! in_array($status, ['open', 'resolved', 'all'], true)) {
            $status = 'open';
        }

        $scans = UnknownScan::query()
            ->with(['user', 'location.parent.parent', 'eventList', 'inventoryList', 'resolvedAsset'])
            ->when($status === 'open', fn ($query) => $query->unresolved())
            ->when($status === 'resolved', fn ($query) => $query->whereNotNull('resolved_asset_id'))
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q')->toString().'%';
                $query->where(function ($inner) use ($term) {
                    $inner->where('scanned_code', 'like', $term)
                        ->orWhere('note', 'like', $term);
                });
            })
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('reports.unknown-scans', [
            'scans' => $scans,
            'status' => $status,
            'q' => $request->string('q')->toString(),
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $data = $request->validate([
            'format' => ['nullable', 'in:xlsx,csv'],
            'status' => ['nullable', 'in:open,resolved,all'],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $status = $data['status'] ?? 'open';
        $format = $data['format'] ?? 'xlsx';

        $scans = UnknownScan::query()
            ->with(['user', 'location.parent.parent', 'eventList', 'inventoryList', 'resolvedAsset'])
            ->when($status === 'open', fn ($query) => $query->unresolved())
            ->when($status === 'resolved', fn ($query) => $query->whereNotNull('resolved_asset_id'))
            ->when(! empty($data['q']), function ($query) use ($data) {
                $term = '%'.$data['q'].'%';
                $query->where(function ($inner) use ($term) {
                    $inner->where('scanned_code', 'like', $term)
                        ->orWhere('note', 'like', $term);
                });
            })
            ->latest()
            ->get();

        $filename = 'unknown-scans-'.now()->format('Ymd-His').'.'.$format;

        return Excel::download(new UnknownScansExport($scans), $filename);
    }

    public function update(Request $request, UnknownScan $unknownScan, ScanService $scanService): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:5000'],
        ]);

        $unknownScan->update([
            'note' => $data['note'] ?? null,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Note saved.',
                'unknown_scan' => $scanService->unknownScanSummary($unknownScan->fresh()),
            ]);
        }

        return back()->with('status', 'Note saved.');
    }

    public function destroyAll(): RedirectResponse
    {
        $deleted = UnknownScan::clearAll();

        return redirect()
            ->route('reports.unknown-scans')
            ->with('status', $deleted === 1
                ? 'Cleared 1 unknown scan.'
                : "Cleared {$deleted} unknown scans.");
    }

    public function deduplicate(): RedirectResponse
    {
        // #region agent log
        $logPath = base_path('debug-5c1253.log');
        file_put_contents($logPath, json_encode([
            'sessionId' => '5c1253',
            'hypothesisId' => 'B',
            'location' => 'UnknownScanController.php:deduplicate',
            'message' => 'deduplicate_entered',
            'data' => ['total_rows' => UnknownScan::query()->count()],
            'timestamp' => (int) (microtime(true) * 1000),
            'runId' => 'post-fix',
        ])."\n", FILE_APPEND);
        // #endregion

        $deleted = UnknownScan::deleteDuplicatesKeepingLatest();

        // #region agent log
        file_put_contents($logPath, json_encode([
            'sessionId' => '5c1253',
            'hypothesisId' => 'C',
            'location' => 'UnknownScanController.php:deduplicate',
            'message' => 'deduplicate_result',
            'data' => [
                'deleted' => $deleted,
                'remaining_rows' => UnknownScan::query()->count(),
            ],
            'timestamp' => (int) (microtime(true) * 1000),
            'runId' => 'post-fix',
        ])."\n", FILE_APPEND);
        // #endregion

        return redirect()
            ->route('reports.unknown-scans')
            ->with('status', $deleted === 0
                ? 'No duplicate unknown scans found.'
                : ($deleted === 1
                    ? 'Removed 1 duplicate unknown scan.'
                    : "Removed {$deleted} duplicate unknown scans."));
    }
}
