<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardStatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardApiController extends Controller
{
    public function stats(Request $request, DashboardStatsService $stats): JsonResponse
    {
        return response()->json($stats->stats($request->user()));
    }
}
