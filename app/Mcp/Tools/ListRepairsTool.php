<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\AuthorizesMcpTools;
use App\Models\Repair;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('List repairs (default: active). Optional status filter and search.')]
class ListRepairsTool extends Tool
{
    use AuthorizesMcpTools;

    public function handle(Request $request): Response
    {
        $user = $this->requireMcpUser($request);
        if ($user instanceof Response) {
            return $user;
        }

        $data = $request->validate([
            'status' => ['nullable', 'string', 'max:32'],
            'q' => ['nullable', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $status = trim((string) ($data['status'] ?? 'active'));
        $limit = (int) ($data['limit'] ?? 25);

        $query = Repair::query()
            ->with(['asset:id,name,fmi_ast,tp_barcode', 'assignee:id,name,email'])
            ->orderByDesc('started_at');

        if ($status === 'active') {
            $query->active();
        } elseif ($status !== '' && $status !== 'all') {
            $query->where('status', $status);
        }

        if (! empty($data['q'])) {
            $query->search($data['q']);
        }

        $repairs = $query->limit($limit)->get();

        return $this->jsonResult([
            'count' => $repairs->count(),
            'repairs' => $repairs->map(fn (Repair $repair) => [
                'id' => $repair->id,
                'status' => $repair->status instanceof \BackedEnum ? $repair->status->value : $repair->status,
                'summary' => $repair->summary,
                'asset_id' => $repair->asset_id,
                'asset' => $repair->asset?->name,
                'assignee' => $repair->assignee?->email,
                'started_at' => optional($repair->started_at)?->toIso8601String(),
            ])->all(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()->description('active (default), all, open, in_progress, completed, cancelled.'),
            'q' => $schema->string()->description('Optional search.'),
            'limit' => $schema->integer()->description('Max rows (default 25).'),
        ];
    }
}
