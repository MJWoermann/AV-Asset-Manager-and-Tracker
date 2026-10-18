<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\AuthorizesMcpTools;
use App\Models\Asset;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Search assets by free-text query. Returns a compact list (max 50).')]
class SearchAssetsTool extends Tool
{
    use AuthorizesMcpTools;

    public function handle(Request $request): Response
    {
        $user = $this->requireMcpUser($request);
        if ($user instanceof Response) {
            return $user;
        }

        $data = $request->validate([
            'q' => ['required', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $limit = (int) ($data['limit'] ?? 25);
        $q = trim($data['q']);

        $assets = Asset::query()
            ->with(['itemType:id,name', 'location:id,name'])
            ->search($q)
            ->orderBySearchRelevance($q)
            ->limit($limit)
            ->get(['id', 'name', 'status', 'fmi_ast', 'tp_barcode', 'rig_tag', 'serial_number', 'location_id', 'item_type_id', 'department_id']);

        return $this->jsonResult([
            'count' => $assets->count(),
            'assets' => $assets->map(fn (Asset $asset) => [
                'id' => $asset->id,
                'name' => $asset->name,
                'status' => $asset->status instanceof \BackedEnum ? $asset->status->value : $asset->status,
                'fmi_ast' => $asset->fmi_ast,
                'tp_barcode' => $asset->tp_barcode,
                'rig_tag' => $asset->rig_tag,
                'serial_number' => $asset->serial_number,
                'location' => $asset->location?->name,
                'item_type' => $asset->itemType?->name,
            ])->all(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'q' => $schema->string()->description('Search term.')->required(),
            'limit' => $schema->integer()->description('Max results (1–50). Default 25.'),
        ];
    }
}
