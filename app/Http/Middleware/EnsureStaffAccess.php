<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffAccess
{
    /**
     * Staff routes. External repairers stay on the assignment-scoped group.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->hasStaffAccess()) {
            abort(403, 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}
