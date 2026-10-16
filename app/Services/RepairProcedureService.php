<?php

namespace App\Services;

use App\Models\RepairProcedure;
use App\Models\RepairProcedureStep;
use Illuminate\Support\Facades\DB;

class RepairProcedureService
{
    /**
     * @param  array<string, mixed>  $data
     * @param  list<array{title: string, body?: string|null, is_required?: bool, sort_order?: int}>  $steps
     * @param  list<array{spare_part_id: int, quantity?: int, sort_order?: int}>  $recommendedParts
     */
    public function create(array $data, array $steps = [], array $recommendedParts = []): RepairProcedure
    {
        return DB::transaction(function () use ($data, $steps, $recommendedParts): RepairProcedure {
            $procedure = RepairProcedure::query()->create($this->procedureAttributes($data));
            $this->syncSteps($procedure, $steps);
            $this->syncRecommendedParts($procedure, $recommendedParts);

            return $procedure->fresh(['steps', 'recommendedSpareParts', 'manufacturerModel', 'itemType', 'sparePart']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array{title: string, body?: string|null, is_required?: bool, sort_order?: int}>  $steps
     * @param  list<array{spare_part_id: int, quantity?: int, sort_order?: int}>  $recommendedParts
     */
    public function update(
        RepairProcedure $procedure,
        array $data,
        array $steps = [],
        array $recommendedParts = [],
    ): RepairProcedure {
        return DB::transaction(function () use ($procedure, $data, $steps, $recommendedParts): RepairProcedure {
            $procedure->update($this->procedureAttributes($data));
            $this->syncSteps($procedure, $steps);
            $this->syncRecommendedParts($procedure, $recommendedParts);

            return $procedure->fresh(['steps', 'recommendedSpareParts', 'manufacturerModel', 'itemType', 'sparePart']);
        });
    }

    public function delete(RepairProcedure $procedure): void
    {
        $procedure->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function procedureAttributes(array $data): array
    {
        return [
            'title' => (string) $data['title'],
            'summary' => $this->nullableString($data['summary'] ?? null),
            'safety_notes' => $this->nullableString($data['safety_notes'] ?? null),
            'tools_required' => $this->nullableString($data['tools_required'] ?? null),
            'estimated_minutes' => isset($data['estimated_minutes']) && $data['estimated_minutes'] !== ''
                ? (int) $data['estimated_minutes']
                : null,
            'manufacturer_model_id' => $data['manufacturer_model_id'] ?? null,
            'item_type_id' => $data['item_type_id'] ?? null,
            'spare_part_id' => $data['spare_part_id'] ?? null,
        ];
    }

    /**
     * @param  list<array{title: string, body?: string|null, is_required?: bool, sort_order?: int}>  $steps
     */
    protected function syncSteps(RepairProcedure $procedure, array $steps): void
    {
        $procedure->steps()->delete();

        foreach (array_values($steps) as $index => $step) {
            $title = trim((string) ($step['title'] ?? ''));
            if ($title === '') {
                continue;
            }

            RepairProcedureStep::query()->create([
                'repair_procedure_id' => $procedure->id,
                'sort_order' => (int) ($step['sort_order'] ?? $index),
                'title' => $title,
                'body' => $this->nullableString($step['body'] ?? null),
                'is_required' => (bool) ($step['is_required'] ?? true),
            ]);
        }
    }

    /**
     * @param  list<array{spare_part_id: int, quantity?: int, sort_order?: int}>  $recommendedParts
     */
    protected function syncRecommendedParts(RepairProcedure $procedure, array $recommendedParts): void
    {
        $sync = [];
        foreach (array_values($recommendedParts) as $index => $row) {
            $partId = (int) ($row['spare_part_id'] ?? 0);
            if ($partId < 1) {
                continue;
            }

            $sync[$partId] = [
                'quantity' => max(1, (int) ($row['quantity'] ?? 1)),
                'sort_order' => (int) ($row['sort_order'] ?? $index),
            ];
        }

        $procedure->recommendedSpareParts()->sync($sync);
    }

    protected function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
