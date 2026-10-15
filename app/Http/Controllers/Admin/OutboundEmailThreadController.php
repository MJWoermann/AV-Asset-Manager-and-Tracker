<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OutboundEmailThread;
use Illuminate\View\View;

class OutboundEmailThreadController extends Controller
{
    public function index(): View
    {
        $threads = OutboundEmailThread::query()
            ->with(['asset', 'sender'])
            ->latest()
            ->paginate(50);

        return view('admin.email.outbound.index', [
            'threads' => $threads,
        ]);
    }
}
