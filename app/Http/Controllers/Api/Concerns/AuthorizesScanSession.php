<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\ScanSession;
use Illuminate\Http\Request;

trait AuthorizesScanSession
{
    protected function authorizeScanSession(Request $request, ScanSession $scan): void
    {
        abort_unless(
            $scan->user_id === $request->user()->id || $request->user()->hasRole('admin'),
            403,
        );
    }
}
