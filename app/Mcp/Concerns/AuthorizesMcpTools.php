<?php

namespace App\Mcp\Concerns;

use App\Models\User;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

trait AuthorizesMcpTools
{
    /**
     * MCP is limited to staff roles (no external_repairer).
     */
    protected function requireMcpUser(Request $request): User|Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return Response::error('Unauthenticated. Connect via Passport OAuth (auth:api) for Copilot Studio.');
        }

        if (! $user->hasAnyRole(['admin', 'inventory_manager', 'scanner'])) {
            return Response::error('Forbidden. MCP requires admin, inventory_manager, or scanner. external_repairer is not allowed.');
        }

        return $user;
    }

    /**
     * @param  list<string>  $roles
     */
    protected function requireRoles(Request $request, array $roles): User|Response
    {
        $user = $this->requireMcpUser($request);

        if ($user instanceof Response) {
            return $user;
        }

        if (! $user->hasAnyRole($roles)) {
            return Response::error('Forbidden. Required role: '.implode('|', $roles).'.');
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
