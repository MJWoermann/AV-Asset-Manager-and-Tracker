<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\AuthorizesMcpTools;
use App\Models\Repair;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Show one repair with steps and linked asset summary.')]
class GetRepairTool extends Tool
{
    use AuthorizesMcpTools;

    public function handle(Request $request): Response
    {
        $user = $this->requirePermission($request, 'repairs:read');
        if ($user instanceof Response) {
            return $user;
        }

        $data = $request->validate([
            'repair_id' => ['required', 'integer', 'exists:repairs,id'],
        ]);

        $repair = Repair::query()
            ->with(['asset:id,name,status,fmi_ast,tp_barcode', 'steps', 'assignee:id,name,email', 'procedure:id,name'])
            ->findOrFail((int) $data['repair_id']);

        return $this->jsonResult([
            'repair' => [
                'id' => $repair->id,
                'status' => $repair->status instanceof \BackedEnum ? $repair->status->value : $repair->status,
                'summary' => $repair->summary,
                'notes' => $repair->notes,
                'resolution_summary' => $repair->resolution_summary,
                'asset_id' => $repair->asset_id,
                'asset' => $repair->asset ? [
                    'id' => $repair->asset->id,
                    'name' => $repair->asset->name,
                    'status' => $repair->asset->status instanceof \BackedEnum
                        ? $repair->asset->status->value
                        : $repair->asset->status,
                    'fmi_ast' => $repair->asset->fmi_ast,
                    'tp_barcode' => $repair->asset->tp_barcode,
                ] : null,
                'assignee' => $repair->assignee?->email,
                'procedure' => $repair->procedure?->name,
                'started_at' => optional($repair->started_at)?->toIso8601String(),
                'completed_at' => optional($repair->completed_at)?->toIso8601String(),
                'steps' => $repair->steps->map(fn ($step) => [
                    'id' => $step->id,
                    'title' => $step->title,
                    'is_required' => (bool) $step->is_required,
                    'completed_at' => optional($step->completed_at)?->toIso8601String(),
                ])->all(),
            ],
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'repair_id' => $schema->integer()->description('Repair id.')->required(),
        ];
    }
}
