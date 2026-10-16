<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AssetConnectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConnectionMapApiController extends Controller
{
    public function index(Request $request, AssetConnectionService $connections): JsonResponse
    {
        $assetId = $request->input('asset_id');
        $assetId = $assetId !== null && $assetId !== '' ? (int) $assetId : null;

        $filters = [
            'connection_type_ids' => array_values(array_filter(
                (array) $request->input('connection_type_ids', []),
                fn ($id) => $id !== null && $id !== ''
            )),
            'location_id' => $request->input('location_id'),
            'item_type_id' => $request->input('item_type_id'),
            'search' => $request->string('search')->toString(),
            'asset_id' => $assetId,
        ];

        return response()->json($connections->buildGraph($filters));
    }
}
