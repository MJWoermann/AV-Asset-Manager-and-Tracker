<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * For scanner / inventory_manager users, queue a hint cookie so the next request
 * can raise session.lifetime before StartSession (all-day stocktake shifts).
 * Admin-only accounts keep the default lifetime.
 */
class SyncScannerSessionLifetimeHint
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();
        $cookieName = ApplyScannerSessionLifetime::COOKIE;

        if ($user && $user->can('scan')) {
            $minutes = (int) config('session.lifetime_scanner', 720);
            config(['session.lifetime' => $minutes]);

            return $this->withCookie($response, cookie(
                $cookieName,
                (string) $minutes,
                $minutes,
                config('session.path', '/'),
                config('session.domain'),
                (bool) config('session.secure', false),
                true,
                false,
                config('session.same_site', 'lax'),
            ));
        }

        if ($request->cookies->has($cookieName)) {
            return $this->withCookie($response, cookie()->forget($cookieName));
        }

        return $response;
    }

    /**
     * Attach a cookie on Laravel responses and Symfony streamed/binary responses.
     */
    protected function withCookie(Response $response, mixed $cookie): Response
    {
        if (method_exists($response, 'withCookie')) {
            return $response->withCookie($cookie);
        }

        $response->headers->setCookie($cookie);

        return $response;
    }
}
