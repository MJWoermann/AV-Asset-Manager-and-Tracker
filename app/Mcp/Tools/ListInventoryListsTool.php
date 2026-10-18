<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\AuthorizesMcpTools;
use App\Models\AssetList;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('List inventory and/or event asset lists with item counts.')]
class ListInventoryListsTool extends Tool
{
    use AuthorizesMcpTools;

    public function handle(Request $request): Response
    {
        $user = $this->requireMcpUser($request);
        if ($user instanceof Response) {
            return $user;
        }

        $data = $request->validate([
            'type' => ['nullable', 'in:inventory,event,all'],
        ]);

        $type = $data['type'] ?? 'all';

        $lists = AssetList::query()
            ->withCount('items')
            ->when($type !== 'all', fn ($q) => $q->where('type', $type))
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'description', 'department_id']);

        return $this->jsonResult([
            'count' => $lists->count(),
            'lists' => $lists->map(fn (AssetList $list) => [
                'id' => $list->id,
                'name' => $list->name,
                'type' => $list->type,
                'description' => $list->description,
                'department_id' => $list->department_id,
                'items_count' => $list->items_count,
            ])->all(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->description('Filter: inventory, event, or all (default).'),
        ];
    }
}
