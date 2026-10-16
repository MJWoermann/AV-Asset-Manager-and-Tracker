<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class MaintenanceHubController extends Controller
{
    public function index(): View
    {
        return view('maintenance.index');
    }
}
