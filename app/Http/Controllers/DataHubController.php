<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DataHubController extends Controller
{
    public function index(): View
    {
        return view('data.index');
    }
}
