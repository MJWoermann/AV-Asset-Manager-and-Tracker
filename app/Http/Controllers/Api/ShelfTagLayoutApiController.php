<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\AssetShelfTagPrintController;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreShelfTagLayoutRequest;
use App\Http\Requests\UpdateShelfTagLayoutRequest;
use App\Models\Asset;
use App\Models\ShelfTagLayout;
use App\Services\ShelfTagRenderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShelfTagLayoutApiController extends Controller
{
    public function index(): JsonResponse
    {
        $layouts = ShelfTagLayout::query()->orderBy('name')->get();

        return response()->json(['data' => $layouts]);
    }

    public function show(ShelfTagLayout $layout): JsonResponse
    {
        return response()->json($layout);
    }

    public function store(StoreShelfTagLayoutRequest $request): JsonResponse
    {
        $layout = ShelfTagLayout::create($request->validated());

        return response()->json($layout, 201);
    }

    public function update(UpdateShelfTagLayoutRequest $request, ShelfTagLayout $layout): JsonResponse
    {
        $layout->update($request->validated());

        return response()->json($layout->fresh());
    }

    public function destroy(Request $request, ShelfTagLayout $layout): JsonResponse
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'inventory_manager']), 403);
        $layout->delete();

        return response()->json(['message' => 'Layout deleted.']);
    }

    public function assetLabelPdf(Request $request, Asset $asset): Response
    {
        return app(AssetShelfTagPrintController::class)->pdf($request, $asset, app(ShelfTagRenderService::class));
    }
}
