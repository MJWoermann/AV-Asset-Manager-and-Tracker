<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Apply an extended session lifetime before StartSession when a prior request
 * left the scanner-shift hint cookie (set by SyncScannerSessionLifetimeHint).
 */
class ApplyScannerSessionLifetime
{
    public const COOKIE = 'av_session_lifetime';

    public function handle(Request $request, Closure $next): Response
    {
        $hint = (int) $request->cookie(self::COOKIE, 0);
        $base = (int) config('session.lifetime');
        $scanner = (int) config('session.lifetime_scanner', 720);
        $max = max($base, $scanner, 1440);

        if ($hint >= $base && $hint <= $max) {
            config(['session.lifetime' => $hint]);
        }

        return $next($request);
    }
}
