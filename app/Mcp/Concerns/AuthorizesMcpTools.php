<?php

namespace App\Mcp\Concerns;

use App\Models\User;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

trait AuthorizesMcpTools
{
    /**
     * Staff with at least one operational permission. External repairers stay out.
     */
    protected function requireMcpUser(Request $request): User|Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return Response::error('Unauthenticated. Connect via Passport OAuth (auth:api) for Copilot Studio.');
        }

        if (! $user->hasStaffAccess()) {
            return Response::error('Forbidden. MCP is limited to staff. external_repairer is not allowed.');
        }

        return $user;
    }

    /**
     * Same operational permission as the matching web and REST route.
     * Custom roles pass when they hold that permission. Admin passes every operational check.
     */
    protected function requirePermission(Request $request, string $permission): User|Response
    {
        $user = $this->requireMcpUser($request);

        if ($user instanceof Response) {
            return $user;
        }

        if (! $user->can($permission)) {
            return Response::error('Forbidden. Required permission: '.$permission.'.');
        }

        return $user;
    }

    /**
     * Compact JSON text for Copilot Studio tool results.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function jsonResult(array $payload): Response
    {
        return Response::text(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
