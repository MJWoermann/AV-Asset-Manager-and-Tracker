<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\AuthorizesMcpTools;
use App\Models\Asset;
use App\Services\RepairService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create a repair with a new fault on an asset via RepairService::startWithNewFault.')]
class CreateRepairTool extends Tool
{
    use AuthorizesMcpTools;

    public function __construct(private RepairService $repairs) {}

    public function handle(Request $request): Response
    {
        $user = $this->requirePermission($request, 'repairs:write');
        if ($user instanceof Response) {
            return $user;
        }

        $data = $request->validate([
            'asset_id' => ['required', 'integer', 'exists:assets,id'],
            'summary' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'repair_procedure_id' => ['nullable', 'integer', 'exists:repair_procedures,id'],
            'assignee_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'set_asset_maintenance' => ['nullable', 'boolean'],
        ]);

        $asset = Asset::query()->findOrFail((int) $data['asset_id']);

        $repair = $this->repairs->startWithNewFault($asset, [
            'summary' => $data['summary'],
            'notes' => $data['notes'] ?? null,
            'repair_procedure_id' => $data['repair_procedure_id'] ?? null,
            'assignee_user_id' => $data['assignee_user_id'] ?? null,
            'set_asset_maintenance' => $data['set_asset_maintenance'] ?? true,
        ], $user);

        return $this->jsonResult([
            'message' => 'Repair created.',
            'repair' => [
                'id' => $repair->id,
                'status' => $repair->status instanceof \BackedEnum ? $repair->status->value : $repair->status,
                'summary' => $repair->summary,
                'asset_id' => $repair->asset_id,
            ],
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'asset_id' => $schema->integer()->description('Asset to repair.')->required(),
            'summary' => $schema->string()->description('Fault / repair summary.')->required(),
            'notes' => $schema->string(),
            'repair_procedure_id' => $schema->integer(),
            'assignee_user_id' => $schema->integer(),
            'set_asset_maintenance' => $schema->boolean()->description('Set asset status to maintenance (default true).'),
        ];
    }
}
