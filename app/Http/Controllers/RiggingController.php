<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class RiggingController extends Controller
{
    public function index(): View
    {
        return view('rigging.index');
    }
}
