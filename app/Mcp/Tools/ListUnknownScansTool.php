<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\AuthorizesMcpTools;
use App\Models\UnknownScan;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('List unknown barcode scans awaiting resolution (default: open).')]
class ListUnknownScansTool extends Tool
{
    use AuthorizesMcpTools;

    public function handle(Request $request): Response
    {
        $user = $this->requirePermission($request, 'reports:read');
        if ($user instanceof Response) {
            return $user;
        }

        $data = $request->validate([
            'status' => ['nullable', 'in:open,resolved,all'],
            'q' => ['nullable', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $status = $data['status'] ?? 'open';
        $limit = (int) ($data['limit'] ?? 25);

        $scans = UnknownScan::query()
            ->with(['location:id,name', 'resolvedAsset:id,name'])
            ->when($status === 'open', fn ($q) => $q->unresolved())
            ->when($status === 'resolved', fn ($q) => $q->whereNotNull('resolved_asset_id'))
            ->when(! empty($data['q']), fn ($q) => $q->search($data['q']))
            ->latest()
            ->limit($limit)
            ->get();

        return $this->jsonResult([
            'count' => $scans->count(),
            'unknown_scans' => $scans->map(fn (UnknownScan $scan) => [
                'id' => $scan->id,
                'scanned_code' => $scan->scanned_code,
                'quantity' => $scan->quantity,
                'note' => $scan->note,
                'location' => $scan->location?->name,
                'resolved_asset_id' => $scan->resolved_asset_id,
                'resolved_asset' => $scan->resolvedAsset?->name,
                'created_at' => optional($scan->created_at)?->toIso8601String(),
            ])->all(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()->description('open (default), resolved, or all.'),
            'q' => $schema->string()->description('Optional search term.'),
            'limit' => $schema->integer()->description('Max rows (default 25).'),
        ];
    }
}
