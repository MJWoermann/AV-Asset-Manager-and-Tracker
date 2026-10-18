<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\AuthorizesMcpTools;
use App\Models\AssetList;
use App\Services\ListComparisonService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Compare a scanned/event list against an inventory list: matched, inventory_only, scanned_only.')]
class CompareListsTool extends Tool
{
    use AuthorizesMcpTools;

    public function __construct(private ListComparisonService $comparison) {}

    public function handle(Request $request): Response
    {
        $user = $this->requireMcpUser($request);
        if ($user instanceof Response) {
            return $user;
        }

        $data = $request->validate([
            'scanned_list_id' => ['required', 'integer', 'exists:asset_lists,id'],
            'inventory_list_id' => ['required', 'integer', 'exists:asset_lists,id', 'different:scanned_list_id'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $limit = (int) ($data['limit'] ?? 50);

        $result = $this->comparison->compare(
            AssetList::query()->findOrFail((int) $data['scanned_list_id']),
            AssetList::query()->findOrFail((int) $data['inventory_list_id']),
        );

        $summarize = function ($rows) use ($limit) {
            return [
                'count' => $rows->count(),
                'sample' => $rows->take($limit)->map(fn ($row) => [
                    'asset_id' => $row->asset_id ?? null,
                    'name' => $row->name ?? null,
                    'fmi_ast' => $row->fmi_ast ?? null,
                    'tp_barcode' => $row->tp_barcode ?? null,
                    'inventory_qty' => $row->inventory_qty ?? $row->quantity ?? null,
                    'scanned_qty' => $row->scanned_qty ?? null,
                ])->values()->all(),
            ];
        };

        return $this->jsonResult([
            'matched' => $summarize($result['matched']),
            'inventory_only' => $summarize($result['inventory_only']),
            'scanned_only' => $summarize($result['scanned_only']),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'scanned_list_id' => $schema->integer()->description('Event/scanned list id.')->required(),
            'inventory_list_id' => $schema->integer()->description('Inventory list id.')->required(),
            'limit' => $schema->integer()->description('Max sample rows per section (default 50).'),
        ];
    }
}
