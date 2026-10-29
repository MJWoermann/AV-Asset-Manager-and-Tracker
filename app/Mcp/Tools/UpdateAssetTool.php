<?php

namespace App\Mcp\Tools;

use App\Enums\AssetStatus;
use App\Mcp\Concerns\AuthorizesMcpTools;
use App\Models\Asset;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Update an existing asset (partial fields).')]
class UpdateAssetTool extends Tool
{
    use AuthorizesMcpTools;

    public function handle(Request $request): Response
    {
        $user = $this->requirePermission($request, 'assets:write');
        if ($user instanceof Response) {
            return $user;
        }

        $data = $request->validate([
            'asset_id' => ['required', 'integer', 'exists:assets,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'item_type_id' => ['sometimes', 'integer', 'exists:item_types,id'],
            'description' => ['nullable', 'string'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'parent_id' => ['nullable', 'integer', 'exists:assets,id'],
            'status' => ['sometimes', 'nullable', Rule::enum(AssetStatus::class)],
            'fmi_ast' => ['nullable', 'string', 'max:255'],
            'tp_barcode' => ['nullable', 'string', 'max:255'],
            'rig_tag' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'manufacturer_id' => ['nullable', 'integer', 'exists:manufacturers,id'],
            'manufacturer_model_id' => ['nullable', 'integer', 'exists:manufacturer_models,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $asset = Asset::query()->findOrFail((int) $data['asset_id']);
        unset($data['asset_id']);

        if (isset($data['status']) && $data['status'] instanceof AssetStatus) {
            $data['status'] = $data['status']->value;
        }

        if ($data === []) {
            return Response::error('No fields to update.');
        }

        $asset->update($data);

        return $this->jsonResult([
            'message' => 'Asset updated.',
            'asset' => [
                'id' => $asset->id,
                'name' => $asset->name,
                'status' => $asset->status instanceof AssetStatus ? $asset->status->value : $asset->status,
                'fmi_ast' => $asset->fmi_ast,
                'tp_barcode' => $asset->tp_barcode,
                'location_id' => $asset->location_id,
            ],
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'asset_id' => $schema->integer()->description('Asset id.')->required(),
            'name' => $schema->string(),
            'item_type_id' => $schema->integer(),
            'description' => $schema->string(),
            'location_id' => $schema->integer(),
            'parent_id' => $schema->integer(),
            'status' => $schema->string(),
            'fmi_ast' => $schema->string(),
            'tp_barcode' => $schema->string(),
            'rig_tag' => $schema->string(),
            'serial_number' => $schema->string(),
            'manufacturer_id' => $schema->integer(),
            'manufacturer_model_id' => $schema->integer(),
            'notes' => $schema->string(),
        ];
    }
}
