<?php

namespace App\Mcp\Tools;

use App\Enums\AssetStatus;
use App\Enums\RepairStatus;
use App\Mcp\Concerns\AuthorizesMcpTools;
use App\Models\Repair;
use App\Services\RepairService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Update repair status: in_progress, completed, or cancelled (calls RepairService).')]
class UpdateRepairStatusTool extends Tool
{
    use AuthorizesMcpTools;

    public function __construct(private RepairService $repairs) {}

    public function handle(Request $request): Response
    {
        $user = $this->requireMcpUser($request);
        if ($user instanceof Response) {
            return $user;
        }

        $data = $request->validate([
            'repair_id' => ['required', 'integer', 'exists:repairs,id'],
            'status' => ['required', Rule::in([
                RepairStatus::InProgress->value,
                RepairStatus::Completed->value,
                RepairStatus::Cancelled->value,
            ])],
            'resolution_summary' => ['nullable', 'string', 'max:5000'],
            'reason' => ['nullable', 'string', 'max:5000'],
            'asset_status' => ['nullable', Rule::enum(AssetStatus::class)],
            'notes' => ['nullable', 'string'],
        ]);

        $repair = Repair::query()->findOrFail((int) $data['repair_id']);
        $status = RepairStatus::from($data['status']);

        if ($status === RepairStatus::InProgress) {
            if (! $repair->isActive()) {
                return Response::error('Cannot mark a closed repair as in progress.');
            }
            $repair->update(['status' => RepairStatus::InProgress]);
            $repair = $repair->fresh();
        } elseif ($status === RepairStatus::Completed) {
            $repair = $this->repairs->complete($repair, [
                'resolution_summary' => $data['resolution_summary'] ?? '',
                'asset_status' => $data['asset_status'] ?? null,
                'notes' => $data['notes'] ?? null,
            ], $user);
        } else {
            $repair = $this->repairs->cancel($repair, (string) ($data['reason'] ?? ''), $user);
        }

        return $this->jsonResult([
            'message' => 'Repair status updated.',
            'repair' => [
                'id' => $repair->id,
                'status' => $repair->status instanceof RepairStatus ? $repair->status->value : $repair->status,
                'summary' => $repair->summary,
                'resolution_summary' => $repair->resolution_summary,
                'completed_at' => optional($repair->completed_at)?->toIso8601String(),
            ],
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'repair_id' => $schema->integer()->description('Repair id.')->required(),
            'status' => $schema->string()->description('in_progress, completed, or cancelled.')->required(),
            'resolution_summary' => $schema->string()->description('Required when completing.'),
            'reason' => $schema->string()->description('Required when cancelling.'),
            'asset_status' => $schema->string()->description('Optional asset status after complete.'),
            'notes' => $schema->string(),
        ];
    }
}
