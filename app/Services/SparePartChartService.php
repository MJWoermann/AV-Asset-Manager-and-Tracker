<?php

namespace App\Services;

use App\Enums\SparePartMovementType;
use App\Models\SparePart;
use App\Models\SparePartMovement;
use Illuminate\Support\Collection;

class SparePartChartService
{
    /**
     * @return array{
     *     current_quantity: int,
     *     min_holding: int|null,
     *     max_holding: int|null,
     *     reorder_eta: string|null,
     *     reorder_eta_label: string|null,
     *     points: list<array<string, mixed>>,
     *     restock_markers: list<array<string, mixed>>,
     *     trend: array{slope_per_day: float|null, intercept: float|null, projection: list<array{x: string, y: float}>}|null,
     *     min_line: int|null,
     *     max_line: int|null,
     * }
     */
    public function build(SparePart $part): array
    {
        $movements = $part->movements()
            ->with(['user:id,name', 'againstAsset:id,name', 'files'])
            ->orderBy('recorded_at')
            ->orderBy('id')
            ->get();

        $points = $movements->map(fn (SparePartMovement $movement): array => $this->pointPayload($movement))->values()->all();
        $restockMarkers = $movements
            ->where('type', SparePartMovementType::Restock)
            ->map(fn (SparePartMovement $movement): array => $this->pointPayload($movement))
            ->values()
            ->all();

        $trend = $this->computeTrend($movements, $part->min_holding);

        return [
            'current_quantity' => max(0, (int) $part->quantity_on_hand),
            'min_holding' => $part->min_holding,
            'max_holding' => $part->max_holding,
            'reorder_eta' => $trend['eta'] ?? null,
            'reorder_eta_label' => $trend['eta_label'] ?? null,
            'points' => $points,
            'restock_markers' => $restockMarkers,
            'trend' => $trend['series'] ?? null,
            'min_line' => $part->min_holding,
            'max_line' => $part->max_holding,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function pointPayload(SparePartMovement $movement): array
    {
        $movement->loadMissing(['user:id,name', 'againstAsset:id,name', 'files']);

        return [
            'id' => $movement->id,
            'x' => $movement->recorded_at?->toIso8601String(),
            'y' => (int) $movement->quantity_after,
            'type' => $movement->type->value,
            'type_label' => $movement->type->label(),
            'quantity_before' => (int) $movement->quantity_before,
            'quantity_after' => (int) $movement->quantity_after,
            'quantity_delta' => (int) $movement->quantity_delta,
            'notes' => $movement->notes,
            'reason' => $movement->reason,
            'ticket_ref' => $movement->ticket_ref,
            'user_name' => $movement->user?->name,
            'against_asset_id' => $movement->against_asset_id,
            'against_asset_name' => $movement->againstAsset?->name,
            'recorded_at' => $movement->recorded_at?->format('Y-m-d H:i'),
            'files' => $movement->files->map(fn ($file): array => [
                'id' => $file->id,
                'title' => $file->title,
                'download_url' => route('files.download', $file),
            ])->values()->all(),
        ];
    }

    /**
     * @param  Collection<int, SparePartMovement>  $movements
     * @return array{series: array{slope_per_day: float, intercept: float, projection: list<array{x: string, y: float}>}|null, eta: string|null, eta_label: string|null}
     */
    protected function computeTrend(Collection $movements, ?int $minLevel): array
    {
        return app(InventoryTrendCalculator::class)->compute($movements, $minLevel);
    }
}
