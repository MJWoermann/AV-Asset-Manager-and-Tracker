<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSpareGroupRequest;
use App\Http\Requests\UpdateSpareGroupRequest;
use App\Models\SpareGroup;
use App\Services\SpareGroupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpareGroupApiController extends Controller
{
    public function index(): JsonResponse
    {
        $groups = SpareGroup::query()->withCount('items')->orderBy('name')->get();

        return response()->json(['data' => $groups]);
    }

    public function show(SpareGroup $group): JsonResponse
    {
        $group->load(['items.sparePart']);

        return response()->json($group);
    }

    public function store(StoreSpareGroupRequest $request, SpareGroupService $service): JsonResponse
    {
        $data = $request->validated();
        $group = $service->create($data, $data['items'] ?? []);

        return response()->json($group->load('items'), 201);
    }

    public function update(UpdateSpareGroupRequest $request, SpareGroup $group, SpareGroupService $service): JsonResponse
    {
        $data = $request->validated();
        $service->update($group, $data, $data['items'] ?? []);

        return response()->json($group->fresh(['items']));
    }

    public function destroy(Request $request, SpareGroup $group): JsonResponse
    {
        abort_unless($request->user()->can('spares:manage'), 403);
        $group->delete();

        return response()->json(['message' => 'Spare group deleted.']);
    }
}
