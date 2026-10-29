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

#[Description('Create an asset. Requires the assets:write permission.')]
class CreateAssetTool extends Tool
{
    use AuthorizesMcpTools;

    public function handle(Request $request): Response
    {
        $user = $this->requirePermission($request, 'assets:write');
        if ($user instanceof Response) {
            return $user;
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'item_type_id' => ['required', 'integer', 'exists:item_types,id'],
            'description' => ['nullable', 'string'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'parent_id' => ['nullable', 'integer', 'exists:assets,id'],
            'status' => ['nullable', Rule::enum(AssetStatus::class)],
            'fmi_ast' => ['nullable', 'string', 'max:255'],
            'tp_barcode' => ['nullable', 'string', 'max:255'],
            'rig_tag' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'manufacturer_id' => ['nullable', 'integer', 'exists:manufacturers,id'],
            'manufacturer_model_id' => ['nullable', 'integer', 'exists:manufacturer_models,id'],
            'notes' => ['nullable', 'string'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
        ]);

        if (isset($data['status']) && $data['status'] instanceof AssetStatus) {
            $data['status'] = $data['status']->value;
        }

        $departmentId = $data['department_id'] ?? $user->resolveActiveDepartmentId();
        if ($departmentId === null) {
            return Response::error('Set an active department before creating assets.');
        }

        if (! $user->canUseDepartment((int) $departmentId)) {
            return Response::error('You cannot use that department.');
        }

        $data['department_id'] = (int) $departmentId;

        $asset = Asset::query()->create($data);

        return $this->jsonResult([
            'message' => 'Asset created.',
            'asset' => [
                'id' => $asset->id,
                'name' => $asset->name,
                'status' => $asset->status instanceof AssetStatus ? $asset->status->value : $asset->status,
                'department_id' => $asset->department_id,
                'fmi_ast' => $asset->fmi_ast,
                'tp_barcode' => $asset->tp_barcode,
            ],
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Asset name.')->required(),
            'item_type_id' => $schema->integer()->description('Item type id.')->required(),
            'description' => $schema->string(),
            'location_id' => $schema->integer(),
            'parent_id' => $schema->integer(),
            'status' => $schema->string()->description('Asset status value.'),
            'fmi_ast' => $schema->string(),
            'tp_barcode' => $schema->string(),
            'rig_tag' => $schema->string(),
            'serial_number' => $schema->string(),
            'manufacturer_id' => $schema->integer(),
            'manufacturer_model_id' => $schema->integer(),
            'notes' => $schema->string(),
            'department_id' => $schema->integer()->description('Defaults to active department.'),
        ];
    }
}
