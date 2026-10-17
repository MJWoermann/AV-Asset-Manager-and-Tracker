<?php

namespace App\Http\Controllers\Api;

use App\Exports\UnknownScansExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\LinkUnknownScanRequest;
use App\Models\Asset;
use App\Models\UnknownScan;
use App\Services\ScanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UnknownScanApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $status = $request->string('status')->toString();
        if (! in_array($status, ['open', 'resolved', 'all'], true)) {
            $status = 'open';
        }

        $scans = UnknownScan::query()
            ->with(['user', 'location', 'resolvedAsset'])
            ->when($status === 'open', fn ($query) => $query->unresolved())
            ->when($status === 'resolved', fn ($query) => $query->whereNotNull('resolved_asset_id'))
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.$request->string('q')->toString().'%';
                $query->where(function ($inner) use ($term): void {
                    $inner->where('scanned_code', 'like', $term)
                        ->orWhere('note', 'like', $term);
                });
            })
            ->latest()
            ->paginate(min($request->integer('per_page', 50), 100));

        return response()->json($scans);
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
            ->with(['user', 'location', 'resolvedAsset'])
            ->when($status === 'open', fn ($query) => $query->unresolved())
            ->when($status === 'resolved', fn ($query) => $query->whereNotNull('resolved_asset_id'))
            ->when(! empty($data['q']), function ($query) use ($data): void {
                $term = '%'.$data['q'].'%';
                $query->where(function ($inner) use ($term): void {
                    $inner->where('scanned_code', 'like', $term)
                        ->orWhere('note', 'like', $term);
                });
            })
            ->latest()
            ->get();

        $filename = 'unknown-scans-'.now()->format('Ymd-His').'.'.$format;

        return Excel::download(new UnknownScansExport($scans), $filename);
    }

    public function update(Request $request, UnknownScan $unknownScan, ScanService $scanService): JsonResponse
    {
        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:5000'],
        ]);

        $unknownScan->update([
            'note' => $data['note'] ?? null,
        ]);

        return response()->json([
            'message' => 'Note saved.',
            'unknown_scan' => $scanService->unknownScanSummary($unknownScan->fresh()),
        ]);
    }

    public function link(
        LinkUnknownScanRequest $request,
        UnknownScan $unknownScan,
        ScanService $scanService,
    ): JsonResponse {
        $asset = Asset::query()->findOrFail($request->integer('asset_id'));

        $unknownScan->linkToExisting(
            $asset,
            $request->boolean('add_to_event_list'),
            $request->boolean('set_identifier'),
            $request->user()?->id,
        );

        return response()->json([
            'message' => 'Unknown scan linked to existing asset.',
            'unknown_scan' => $scanService->unknownScanSummary($unknownScan->fresh(['resolvedAsset'])),
        ]);
    }

    public function destroyAll(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $deleted = UnknownScan::clearAll();

        return response()->json(['message' => 'Unknown scans cleared.', 'deleted' => $deleted]);
    }

    public function deduplicate(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $removed = UnknownScan::deleteDuplicatesKeepingLatest();

        return response()->json(['message' => 'Deduplication complete.', 'removed' => $removed]);
    }
}
