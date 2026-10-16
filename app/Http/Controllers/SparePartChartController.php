<?php

namespace App\Http\Controllers;

use App\Models\SparePart;
use App\Services\SparePartChartService;
use Illuminate\Http\JsonResponse;

class SparePartChartController extends Controller
{
    public function show(SparePart $spare, SparePartChartService $charts): JsonResponse
    {
        return response()->json($charts->build($spare));
    }
}
