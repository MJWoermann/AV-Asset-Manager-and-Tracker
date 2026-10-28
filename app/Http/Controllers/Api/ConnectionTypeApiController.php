<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\GeneratesConnectionTypeSlug;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreConnectionTypeRequest;
use App\Http\Requests\UpdateConnectionTypeRequest;
use App\Models\ConnectionType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConnectionTypeApiController extends Controller
{
    use GeneratesConnectionTypeSlug;

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => ConnectionType::query()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreConnectionTypeRequest $request): JsonResponse
    {
        $data = $request->validated();
        $slug = $this->uniqueSlug($data['name']);

        $type = ConnectionType::query()->create([
            'name' => $data['name'],
            'slug' => $slug,
            'color' => strtolower($data['color']),
            'icon' => $data['icon'],
            'allows_protocol' => $request->boolean('allows_protocol'),
            'allows_bus' => $request->boolean('allows_bus'),
            'sort_order' => (int) ($data['sort_order'] ?? 100),
        ]);

        return response()->json($type, 201);
    }

    public function update(UpdateConnectionTypeRequest $request, ConnectionType $connectionType): JsonResponse
    {
        $data = $request->validated();
        $connectionType->update([
            'name' => $data['name'],
            'slug' => $connectionType->slug,
            'color' => strtolower($data['color']),
            'icon' => $data['icon'],
            'allows_protocol' => $request->boolean('allows_protocol'),
            'allows_bus' => $request->boolean('allows_bus'),
            'sort_order' => (int) ($data['sort_order'] ?? $connectionType->sort_order),
        ]);

        return response()->json($connectionType->fresh());
    }

    public function destroy(Request $request, ConnectionType $connectionType): JsonResponse
    {
        abort_unless($request->user()->can('connections:write'), 403);
        $connectionType->delete();

        return response()->json(['message' => 'Connection type deleted.']);
    }
}
