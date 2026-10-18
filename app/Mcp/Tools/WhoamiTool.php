<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\AuthorizesMcpTools;
use App\Support\DepartmentVisibility;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Return the authenticated MCP user, roles, departments, and active department context.')]
class WhoamiTool extends Tool
{
    use AuthorizesMcpTools;

    public function handle(Request $request): Response
    {
        $user = $this->requireMcpUser($request);
        if ($user instanceof Response) {
            return $user;
        }

        $activeId = $user->resolveActiveDepartmentId(false)
            ?? ($user->preferences[DepartmentVisibility::SESSION_KEY] ?? null);

        return $this->jsonResult([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames()->values()->all(),
            'active_department_id' => $activeId !== null ? (int) $activeId : null,
            'departments' => $user->departments()->orderBy('name')->get(['departments.id', 'departments.name', 'departments.slug'])
                ->map(fn ($d) => ['id' => $d->id, 'name' => $d->name, 'slug' => $d->slug])
                ->values()
                ->all(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
