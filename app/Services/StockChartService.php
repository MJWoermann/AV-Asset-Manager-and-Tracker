<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Asset;
use App\Models\StockMovement;
use Illuminate\Support\Collection;

class StockChartService
{
    /**
     * @return array{
     *     current_quantity: int,
     *     min_stock_level: int|null,
     *     max_stock_level: int|null,
     *     reorder_eta: string|null,
     *     reorder_eta_label: string|null,
     *     points: list<array<string, mixed>>,
     *     restock_markers: list<array<string, mixed>>,
     *     trend: array{slope_per_day: float|null, intercept: float|null, projection: list<array{x: string, y: float}>}|null,
     *     min_line: int|null,
     *     max_line: int|null,
     * }
     */
    public function build(Asset $asset): array
    {
        $movements = $asset->stockMovements()
            ->with(['user:id,name', 'files'])
            ->orderBy('recorded_at')
            ->orderBy('id')
            ->get();

        $points = $movements->map(fn (StockMovement $movement): array => $this->pointPayload($movement))->values()->all();
        $restockMarkers = $movements
            ->where('type', StockMovementType::Restock)
            ->map(fn (StockMovement $movement): array => $this->pointPayload($movement))
            ->values()
            ->all();

        $trend = $this->computeTrend($movements, $asset->min_stock_level);

        return [
            'current_quantity' => max(0, (int) ($asset->quantity ?? 0)),
            'min_stock_level' => $asset->min_stock_level,
            'max_stock_level' => $asset->max_stock_level,
            'reorder_eta' => $trend['eta'] ?? null,
            'reorder_eta_label' => $trend['eta_label'] ?? null,
            'points' => $points,
            'restock_markers' => $restockMarkers,
            'trend' => $trend['series'] ?? null,
            'min_line' => $asset->min_stock_level,
            'max_line' => $asset->max_stock_level,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function pointPayload(StockMovement $movement): array
    {
        $movement->loadMissing(['user:id,name', 'files']);

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
            'source' => $movement->source->value,
            'user_name' => $movement->user?->name,
            'external_po_number' => $movement->external_po_number,
            'external_po_url' => $movement->external_po_url,
            'recorded_at' => $movement->recorded_at?->format('Y-m-d H:i'),
            'files' => $movement->files->map(fn ($file): array => [
                'id' => $file->id,
                'title' => $file->title,
                'download_url' => route('files.download', $file),
            ])->values()->all(),
        ];
    }

    /**
     * @param  Collection<int, StockMovement>  $movements
     * @return array{series: array{slope_per_day: float, intercept: float, projection: list<array{x: string, y: float}>}|null, eta: string|null, eta_label: string|null}
     */
    protected function computeTrend(Collection $movements, ?int $minLevel): array
    {
        return app(InventoryTrendCalculator::class)->compute($movements, $minLevel);
    }
}
