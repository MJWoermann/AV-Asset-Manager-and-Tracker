<?php

namespace App\Http\Controllers;

use App\Exports\UnknownScansExport;
use App\Http\Requests\LinkUnknownScanRequest;
use App\Models\Asset;
use App\Models\UnknownScan;
use App\Services\ScanService;
use App\Support\ExportRowLimiter;
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
            ->with(['user', 'location.parent.parent.parent.parent', 'eventList', 'inventoryList', 'resolvedAsset'])
            ->when($status === 'open', fn ($query) => $query->unresolved())
            ->when($status === 'resolved', fn ($query) => $query->whereNotNull('resolved_asset_id'))
            ->when($request->filled('q'), function ($query) use ($request) {
                $query->search($request->string('q')->toString());
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

        $query = UnknownScan::query()
            ->with(['user', 'location.parent.parent.parent.parent', 'eventList', 'inventoryList', 'resolvedAsset'])
            ->when($status === 'open', fn ($query) => $query->unresolved())
            ->when($status === 'resolved', fn ($query) => $query->whereNotNull('resolved_asset_id'))
            ->when(! empty($data['q']), function ($query) use ($data) {
                $query->search($data['q']);
            })
            ->latest();

        ExportRowLimiter::constrain($query, 'unknown-scan export');

        $filename = 'unknown-scans-'.now()->format('Ymd-His').'.'.$format;

        return Excel::download(new UnknownScansExport($query->get()), $filename);
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

    public function link(
        LinkUnknownScanRequest $request,
        UnknownScan $unknownScan,
        ScanService $scanService,
    ): JsonResponse|RedirectResponse {
        $asset = Asset::query()->findOrFail($request->integer('asset_id'));

        $unknownScan->linkToExisting(
            $asset,
            $request->boolean('add_to_event_list'),
            $request->boolean('set_identifier'),
            $request->user()?->id,
        );

        $unknownScan = $unknownScan->fresh(['resolvedAsset']);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Unknown scan linked to existing asset.',
                'unknown_scan' => $scanService->unknownScanSummary($unknownScan),
            ]);
        }

        return back()->with('status', 'Unknown scan linked to '.$asset->name.'.');
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
        $deleted = UnknownScan::deleteDuplicatesKeepingLatest();

        return redirect()
            ->route('reports.unknown-scans')
            ->with('status', $deleted === 0
                ? 'No duplicate unknown scans found.'
                : ($deleted === 1
                    ? 'Removed 1 duplicate unknown scan.'
                    : "Removed {$deleted} duplicate unknown scans."));
    }
}
