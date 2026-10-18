<?php

namespace App\Mcp\Resources;

use App\Models\User;
use App\Support\DepartmentVisibility;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Uri('av://me')]
#[Description('Current MCP principal: id, email, roles, active department.')]
class MeResource extends Resource
{
    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return Response::error('Unauthenticated.');
        }

        $activeId = $user->resolveActiveDepartmentId(false)
            ?? ($user->preferences[DepartmentVisibility::SESSION_KEY] ?? null);

        return Response::text(json_encode([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames()->values()->all(),
            'active_department_id' => $activeId !== null ? (int) $activeId : null,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
