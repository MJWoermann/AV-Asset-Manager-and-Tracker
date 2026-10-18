<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\AuthorizesMcpTools;
use App\Models\Department;
use App\Support\DepartmentVisibility;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Set the active department for subsequent department-scoped creates and lookups.')]
class SetActiveDepartmentTool extends Tool
{
    use AuthorizesMcpTools;

    public function handle(Request $request): Response
    {
        $user = $this->requireMcpUser($request);
        if ($user instanceof Response) {
            return $user;
        }

        $data = $request->validate([
            'department_id' => ['required', 'integer', 'exists:departments,id'],
        ]);

        $departmentId = (int) $data['department_id'];

        if (! $user->canUseDepartment($departmentId)) {
            return Response::error('You cannot use that department.');
        }

        session([DepartmentVisibility::SESSION_KEY => $departmentId]);

        $preferences = $user->preferences ?? [];
        $preferences[DepartmentVisibility::SESSION_KEY] = $departmentId;
        $user->forceFill(['preferences' => $preferences])->save();

        $department = Department::query()->find($departmentId);

        return $this->jsonResult([
            'message' => 'Active department updated.',
            'department_id' => $departmentId,
            'department' => $department ? [
                'id' => $department->id,
                'name' => $department->name,
                'slug' => $department->slug,
            ] : null,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'department_id' => $schema->integer()
                ->description('Department id to activate.')
                ->required(),
        ];
    }
}
