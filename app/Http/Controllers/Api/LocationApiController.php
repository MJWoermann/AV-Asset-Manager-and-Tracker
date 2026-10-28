<?php

namespace App\Http\Controllers\Api;

use App\Enums\LocationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkUpdateLocationsRequest;
use App\Http\Requests\DeleteLocationRequest;
use App\Models\Location;
use App\Services\BulkLocationUpdateService;
use App\Support\TextSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocationApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Location::query()->with('parent');

        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->integer('parent_id'));
        }

        if ($type = $request->string('type')->toString()) {
            $query->where('type', $type);
        }

        if ($q = trim($request->string('q')->toString())) {
            TextSearch::constrainColumn($query, $q, 'name');
        }

        $locations = $query->orderByHierarchy()->paginate(min($request->integer('per_page', 100), 200));

        return response()->json($locations);
    }

    public function show(Location $location): JsonResponse
    {
        $location->load(['parent', 'children']);

        return response()->json($location);
    }

    public function store(Request $request): JsonResponse
    {
        $location = Location::create($this->validated($request));

        return response()->json($location, 201);
    }

    public function update(Request $request, Location $location): JsonResponse
    {
        abort_unless($request->user()->can('locations:update'), 403);

        $location->update($this->validated($request));

        return response()->json($location->fresh());
    }

    public function destroy(
        DeleteLocationRequest $request,
        Location $location,
        BulkLocationUpdateService $locations,
    ): JsonResponse {
        $replacement = Location::query()->findOrFail(
            (int) $request->validated('replacement_location_id')
        );

        $locations->deleteWithReplacement($location, $replacement);

        return response()->json(['message' => 'Location deleted.']);
    }

    public function bulkUpdate(BulkUpdateLocationsRequest $request, BulkLocationUpdateService $bulkUpdate): JsonResponse
    {
        $result = $bulkUpdate->handle($request->bulkPayload());

        return response()->json(['message' => 'Bulk update applied.', 'result' => $result]);
    }

    /** @return array<string, mixed> */
    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'room_code' => ['nullable', 'string', 'max:64'],
            'type' => ['required', Rule::enum(LocationType::class)],
            'parent_id' => [
                'nullable',
                Rule::exists('locations', 'id')->whereNot('type', LocationType::Rack->value),
            ],
            'is_portable' => ['sometimes', 'boolean'],
        ], [
            'parent_id.exists' => 'Racks cannot be a parent for another location.',
        ]) + ['is_portable' => $request->boolean('is_portable')];

        $data['room_code'] = filled($data['room_code'] ?? null) ? trim($data['room_code']) : null;

        return $data;
    }
}
