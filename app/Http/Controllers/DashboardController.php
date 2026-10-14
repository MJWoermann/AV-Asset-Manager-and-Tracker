<?php

namespace App\Http\Controllers;

use App\Services\DashboardStatsService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(DashboardStatsService $stats): View
    {
        return view('dashboard', $stats->stats());
    }
}
