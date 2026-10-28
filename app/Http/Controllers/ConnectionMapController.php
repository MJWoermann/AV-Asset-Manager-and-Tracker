<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\ConnectionType;
use App\Models\ItemType;
use App\Models\Location;
use App\Services\AssetConnectionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConnectionMapController extends Controller
{
    public function index(Request $request, AssetConnectionService $connections): View
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

        try {
            $graph = $connections->buildGraph($filters);
        } catch (\Throwable $exception) {
            // #region agent log
            file_put_contents(base_path('debug-3223d1.log'), json_encode([
                'sessionId' => '3223d1',
                'hypothesisId' => 'H5',
                'location' => 'ConnectionMapController.php:index',
                'message' => 'buildGraph threw',
                'data' => [
                    'filters' => $filters['connection_type_ids'] ?? [],
                    'error' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ],
                'timestamp' => (int) round(microtime(true) * 1000),
            ])."\n", FILE_APPEND);
            // #endregion

            throw $exception;
        }

        $focusAsset = $assetId
            ? Asset::query()->find($assetId)
            : null;

        $clearFocusQuery = collect($filters)
            ->except('asset_id')
            ->filter(fn ($value) => $value !== null && $value !== '' && $value !== [])
            ->all();

        return view('reports.connection-map', [
            'graph' => $graph,
            'filters' => $filters,
            'focusAsset' => $focusAsset,
            'clearFocusQuery' => $clearFocusQuery,
            'connectionTypes' => ConnectionType::query()->orderBy('sort_order')->orderBy('name')->get(),
            'locations' => Location::query()->orderBy('name')->get(['id', 'name']),
            'itemTypes' => ItemType::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
