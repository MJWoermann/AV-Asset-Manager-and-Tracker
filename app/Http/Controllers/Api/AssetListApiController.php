<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssetList;
use App\Models\ScanSession;
use App\Services\ListComparisonService;
use App\Services\ScanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetListApiController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(AssetList::withCount('items')->orderBy('name')->get());
    }

    public function show(AssetList $list): JsonResponse
    {
        $list->load(['items.asset']);

        return response()->json($list);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('lists:write'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:inventory,event'],
            'description' => ['nullable', 'string'],
        ]);

        $list = AssetList::create($data + ['created_by' => $request->user()->id]);

        return response()->json($list, 201);
    }

    public function update(Request $request, AssetList $list): JsonResponse
    {
        abort_unless($request->user()->can('lists:write'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:inventory,event'],
            'description' => ['nullable', 'string'],
        ]);
        $list->update($data);

        return response()->json($list->fresh());
    }

    public function destroy(Request $request, AssetList $list): JsonResponse
    {
        abort_unless($request->user()->can('lists:write'), 403);
        $list->delete();

        return response()->json(['message' => 'List deleted.']);
    }

    public function compare(Request $request, ListComparisonService $comparison): JsonResponse
    {
        $data = $request->validate([
            'scanned_list_id' => ['required', 'exists:asset_lists,id'],
            'inventory_list_id' => ['required', 'exists:asset_lists,id', 'different:scanned_list_id'],
        ]);

        $result = $comparison->compare(
            AssetList::findOrFail($data['scanned_list_id']),
            AssetList::findOrFail($data['inventory_list_id'])
        );

        return response()->json($result);
    }

    public function scan(Request $request, ScanService $scanService): JsonResponse
    {
        abort_unless($request->user()->can('scan'), 403);

        $data = $request->validate([
            'scan_session_id' => ['required', 'exists:scan_sessions,id'],
            'code' => ['required', 'string'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'confirm_parent_change' => ['sometimes', 'boolean'],
        ]);

        $session = ScanSession::findOrFail($data['scan_session_id']);
        abort_unless($session->user_id === $request->user()->id || $request->user()->hasRole('admin'), 403);

        $result = $scanService->scanCode(
            $session,
            $data['code'],
            $data['quantity'] ?? 1,
            $request->boolean('confirm_parent_change'),
        );

        return response()->json($result);
    }
}
