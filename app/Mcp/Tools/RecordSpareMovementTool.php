<?php

namespace App\Mcp\Tools;

use App\Enums\SparePartMovementType;
use App\Mcp\Concerns\AuthorizesMcpTools;
use App\Models\SparePart;
use App\Services\SparePartService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Record a spare part stock movement (use/replace/restock/adjustment/count) via SparePartService.')]
class RecordSpareMovementTool extends Tool
{
    use AuthorizesMcpTools;

    public function __construct(private SparePartService $spares) {}

    public function handle(Request $request): Response
    {
        $user = $this->requirePermission($request, 'spares:write');
        if ($user instanceof Response) {
            return $user;
        }

        $data = $request->validate([
            'spare_part_id' => ['nullable', 'integer', 'exists:spare_parts,id'],
            'code' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::enum(SparePartMovementType::class)],
            'quantity' => ['nullable', 'integer'],
            'against_asset_id' => ['nullable', 'integer', 'exists:assets,id'],
            'repair_id' => ['nullable', 'integer', 'exists:repairs,id'],
            'reason' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string'],
            'ticket_ref' => ['nullable', 'string', 'max:255'],
        ]);

        $part = null;
        if (! empty($data['spare_part_id'])) {
            $part = SparePart::query()->findOrFail((int) $data['spare_part_id']);
        } elseif (! empty($data['code'])) {
            $part = SparePart::query()->where('code', trim($data['code']))->first();
        }

        if ($part === null) {
            return Response::error('Provide spare_part_id or code.');
        }

        $movement = $this->spares->record($part, [
            'type' => $data['type'],
            'quantity' => $data['quantity'] ?? null,
            'against_asset_id' => $data['against_asset_id'] ?? null,
            'repair_id' => $data['repair_id'] ?? null,
            'reason' => $data['reason'] ?? null,
            'notes' => $data['notes'] ?? null,
            'ticket_ref' => $data['ticket_ref'] ?? null,
        ], $user);

        $part->refresh();

        return $this->jsonResult([
            'message' => 'Spare movement recorded.',
            'movement' => [
                'id' => $movement->id,
                'type' => $movement->type instanceof SparePartMovementType
                    ? $movement->type->value
                    : $movement->type,
                'quantity_before' => $movement->quantity_before,
                'quantity_after' => $movement->quantity_after,
                'quantity_delta' => $movement->quantity_delta,
            ],
            'spare' => [
                'id' => $part->id,
                'code' => $part->code,
                'quantity_on_hand' => $part->quantity_on_hand,
            ],
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'spare_part_id' => $schema->integer(),
            'code' => $schema->string()->description('Spare code when id not provided.'),
            'type' => $schema->string()->description('use, replace, restock, adjustment, or count.')->required(),
            'quantity' => $schema->integer()->description('Quantity (semantics depend on type).'),
            'against_asset_id' => $schema->integer()->description('Required for use/replace.'),
            'repair_id' => $schema->integer(),
            'reason' => $schema->string()->description('Required for use/replace.'),
            'notes' => $schema->string(),
            'ticket_ref' => $schema->string(),
        ];
    }
}
