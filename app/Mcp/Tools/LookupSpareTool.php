<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\AuthorizesMcpTools;
use App\Models\SparePart;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Look up a spare part by id or code.')]
class LookupSpareTool extends Tool
{
    use AuthorizesMcpTools;

    public function handle(Request $request): Response
    {
        $user = $this->requireMcpUser($request);
        if ($user instanceof Response) {
            return $user;
        }

        $data = $request->validate([
            'id' => ['nullable', 'integer', 'exists:spare_parts,id'],
            'code' => ['nullable', 'string', 'max:255'],
        ]);

        if (empty($data['id']) && empty($data['code'])) {
            return Response::error('Provide id or code.');
        }

        $spare = ! empty($data['id'])
            ? SparePart::query()->find((int) $data['id'])
            : SparePart::query()->where('code', trim((string) $data['code']))->first();

        if ($spare === null) {
            return $this->jsonResult(['found' => false, 'spare' => null]);
        }

        $spare->load(['manufacturer:id,name', 'manufacturerModel:id,name', 'location:id,name']);

        return $this->jsonResult([
            'found' => true,
            'spare' => [
                'id' => $spare->id,
                'code' => $spare->code,
                'component' => $spare->component,
                'quantity_on_hand' => $spare->quantity_on_hand,
                'stock_status' => $spare->stock_status instanceof \BackedEnum
                    ? $spare->stock_status->value
                    : $spare->stock_status,
                'lifecycle_status' => $spare->lifecycle_status instanceof \BackedEnum
                    ? $spare->lifecycle_status->value
                    : $spare->lifecycle_status,
                'manufacturer' => $spare->manufacturer?->name,
                'model' => $spare->manufacturerModel?->name,
                'location' => $spare->location?->name,
            ],
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Spare part id.'),
            'code' => $schema->string()->description('Spare part code.'),
        ];
    }
}
