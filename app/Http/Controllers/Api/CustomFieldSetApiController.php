<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ManagesCustomFieldSets;
use App\Http\Controllers\Controller;
use App\Models\CustomFieldSet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomFieldSetApiController extends Controller
{
    use ManagesCustomFieldSets;

    public function index(): JsonResponse
    {
        $fieldSets = CustomFieldSet::query()
            ->withCount('definitions')
            ->with('itemTypes')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $fieldSets]);
    }

    public function show(CustomFieldSet $customFieldSet): JsonResponse
    {
        $customFieldSet->load('definitions');

        return response()->json($customFieldSet);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('catalog:update'), 403);

        $data = $this->validated($request);

        $fieldSet = DB::transaction(function () use ($data): CustomFieldSet {
            $fieldSet = CustomFieldSet::create([
                'name' => $data['name'],
                'slug' => $this->uniqueSetSlug($data['name']),
                'description' => $data['description'] ?? null,
            ]);

            $this->syncDefinitions($fieldSet, $data['fields'] ?? []);

            return $fieldSet;
        });

        return response()->json($fieldSet->load('definitions'), 201);
    }

    public function update(Request $request, CustomFieldSet $customFieldSet): JsonResponse
    {
        abort_unless($request->user()->can('catalog:update'), 403);

        $data = $this->validated($request, $customFieldSet);

        DB::transaction(function () use ($customFieldSet, $data): void {
            $customFieldSet->update([
                'name' => $data['name'],
                'slug' => $this->uniqueSetSlug($data['name'], $customFieldSet->id),
                'description' => $data['description'] ?? null,
            ]);

            $this->syncDefinitions($customFieldSet, $data['fields'] ?? []);
        });

        return response()->json($customFieldSet->fresh(['definitions']));
    }

    public function destroy(Request $request, CustomFieldSet $customFieldSet): JsonResponse
    {
        abort_unless($request->user()->can('catalog:update'), 403);
        $customFieldSet->delete();

        return response()->json(['message' => 'Custom field set deleted.']);
    }
}
