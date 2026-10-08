<?php

namespace App\Http\Controllers;

use App\Models\AssetList;
use App\Models\Location;
use App\Models\ScanSession;
use App\Services\ScanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScanController extends Controller
{
    public function index(Request $request): View
    {
        $active = ScanSession::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->with(['eventList', 'inventoryList', 'location'])
            ->latest()
            ->first();

        return view('scan.index', [
            'session' => $active,
            'eventLists' => AssetList::where('type', 'event')->orderBy('name')->get(),
            'inventoryLists' => AssetList::where('type', 'inventory')->orderBy('name')->get(),
            'locations' => Location::orderBy('name')->get(),
        ]);
    }

    public function start(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'event_list_id' => ['required', 'exists:asset_lists,id'],
            'inventory_list_id' => ['nullable', 'exists:asset_lists,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
        ]);

        ScanSession::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->update(['status' => 'closed']);

        ScanSession::create($data + [
            'user_id' => $request->user()->id,
            'status' => 'active',
        ]);

        return redirect()->route('scan.index')->with('status', 'Scan session started.');
    }

    public function updateLocation(Request $request, ScanSession $scan): RedirectResponse
    {
        abort_unless($scan->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'location_id' => ['nullable', 'exists:locations,id'],
        ]);
        $scan->update($data);

        return back()->with('status', 'Location updated for next scans.');
    }

    public function scan(Request $request, ScanSession $scan, ScanService $scanService): JsonResponse|RedirectResponse
    {
        abort_unless($scan->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:255'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        try {
            $result = $scanService->scanCode($scan, $data['code'], $data['quantity'] ?? 1);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
            }

            return back()->withErrors($e->errors());
        }

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        $flash = $result['duplicate'] ? 'warning' : 'status';

        return back()->with($flash, $result['message'])->with('inventory_status', $result['inventory_status']);
    }

    public function close(Request $request, ScanSession $scan): RedirectResponse
    {
        abort_unless($scan->user_id === $request->user()->id, 403);
        $scan->update(['status' => 'closed']);

        return redirect()->route('lists.show', $scan->event_list_id)->with('status', 'Scan session closed.');
    }
}
