<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\AuthorizesMcpTools;
use App\Models\Asset;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Look up one asset by id or exact identifier (fmi_ast, tp_barcode, rig_tag, serial_number, mac_address). Falls back to search.')]
class LookupAssetTool extends Tool
{
    use AuthorizesMcpTools;

    public function handle(Request $request): Response
    {
        $user = $this->requireMcpUser($request);
        if ($user instanceof Response) {
            return $user;
        }

        $data = $request->validate([
            'id' => ['nullable', 'integer', 'exists:assets,id'],
            'code' => ['nullable', 'string', 'max:255'],
        ]);

        if (empty($data['id']) && empty($data['code'])) {
            return Response::error('Provide id or code.');
        }

        $asset = null;

        if (! empty($data['id'])) {
            $asset = Asset::query()->find((int) $data['id']);
        } else {
            $code = trim((string) $data['code']);
            $asset = Asset::query()->findByIdentifier($code)->first()
                ?? Asset::query()->search($code)->orderBySearchRelevance($code)->first();
        }

        if ($asset === null) {
            return $this->jsonResult(['found' => false, 'asset' => null]);
        }

        $asset->load(['itemType:id,name,slug', 'location:id,name', 'manufacturer:id,name', 'manufacturerModel:id,name']);

        return $this->jsonResult([
            'found' => true,
            'asset' => [
                'id' => $asset->id,
                'name' => $asset->name,
                'status' => $asset->status instanceof \BackedEnum ? $asset->status->value : $asset->status,
                'fmi_ast' => $asset->fmi_ast,
                'tp_barcode' => $asset->tp_barcode,
                'rig_tag' => $asset->rig_tag,
                'serial_number' => $asset->serial_number,
                'department_id' => $asset->department_id,
                'location_id' => $asset->location_id,
                'location' => $asset->location?->name,
                'item_type' => $asset->itemType?->name,
                'manufacturer' => $asset->manufacturer?->name,
                'model' => $asset->manufacturerModel?->name,
                'parent_id' => $asset->parent_id,
            ],
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Asset id.'),
            'code' => $schema->string()->description('Barcode or identifier to resolve.'),
        ];
    }
}
