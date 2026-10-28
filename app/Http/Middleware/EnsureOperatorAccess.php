<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOperatorAccess
{
    /**
     * Shared routes for staff and external repairers.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || (! $user->hasStaffAccess() && ! $user->isExternalRepairer())) {
            abort(403, 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}
