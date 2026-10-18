<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\AuthorizesMcpTools;
use App\Models\Asset;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Read-only asset audit history (never mutate audit logs).')]
class AssetHistoryTool extends Tool
{
    use AuthorizesMcpTools;

    public function handle(Request $request): Response
    {
        $user = $this->requireMcpUser($request);
        if ($user instanceof Response) {
            return $user;
        }

        $data = $request->validate([
            'asset_id' => ['required', 'integer', 'exists:assets,id'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $asset = Asset::query()->findOrFail((int) $data['asset_id']);
        $limit = (int) ($data['limit'] ?? 25);

        $logs = $asset->auditLogs()
            ->with('user:id,name,email')
            ->limit($limit)
            ->get(['id', 'user_id', 'action', 'old_values', 'new_values', 'created_at']);

        return $this->jsonResult([
            'asset_id' => $asset->id,
            'count' => $logs->count(),
            'history' => $logs->map(fn ($log) => [
                'id' => $log->id,
                'action' => $log->action,
                'user' => $log->user?->email,
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
                'created_at' => optional($log->created_at)?->toIso8601String(),
            ])->all(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'asset_id' => $schema->integer()->description('Asset id.')->required(),
            'limit' => $schema->integer()->description('Max entries (1–100). Default 25.'),
        ];
    }
}
