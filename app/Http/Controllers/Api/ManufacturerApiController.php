<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteManufacturerRequest;
use App\Models\Manufacturer;
use App\Services\ManufacturerCatalogueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ManufacturerApiController extends Controller
{
    public function index(): JsonResponse
    {
        $manufacturers = Manufacturer::query()
            ->with(['models' => fn ($query) => $query->orderBy('name')])
            ->withCount(['assets', 'models'])
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $manufacturers]);
    }

    public function show(Manufacturer $manufacturer): JsonResponse
    {
        $manufacturer->load(['models' => fn ($query) => $query->orderBy('name')]);

        return response()->json($manufacturer);
    }

    public function store(Request $request): JsonResponse
    {
        $manufacturer = Manufacturer::create($this->validated($request));

        return response()->json($manufacturer, 201);
    }

    public function update(Request $request, Manufacturer $manufacturer): JsonResponse
    {
        abort_unless($request->user()->can('catalog:update'), 403);
        $manufacturer->update($this->validated($request, $manufacturer));

        return response()->json($manufacturer->fresh());
    }

    public function destroy(
        DeleteManufacturerRequest $request,
        Manufacturer $manufacturer,
        ManufacturerCatalogueService $catalogue,
    ): JsonResponse {
        if ($request->requiresReplacement()) {
            $replacement = Manufacturer::query()->findOrFail(
                (int) $request->validated('replacement_manufacturer_id')
            );
            $catalogue->deleteManufacturer($manufacturer, $replacement);
        } else {
            $catalogue->deleteWithoutReassignment($manufacturer);
        }

        return response()->json(['message' => 'Manufacturer deleted.']);
    }

    /** @return array<string, mixed> */
    protected function validated(Request $request, ?Manufacturer $manufacturer = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('manufacturers', 'name')->ignore($manufacturer?->id),
            ],
        ]);
    }
}
