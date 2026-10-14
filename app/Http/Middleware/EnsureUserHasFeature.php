<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasFeature
{
    /**
     * Abort with 404 when the authenticated user does not have the given feature flag.
     *
     * Usage: Route::middleware('feature:asset_connections')->group(...)
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasFeature($feature)) {
            abort(404);
        }

        return $next($request);
    }
}
