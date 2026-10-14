<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Asset;
use App\Models\StockMovement;
use Carbon\Carbon;
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
    protected function computeTrend(Collection $movements, ?int $minStockLevel): array
    {
        if ($movements->count() < 2 || $minStockLevel === null) {
            return ['series' => null, 'eta' => null, 'eta_label' => null];
        }

        $origin = $movements->first()->recorded_at?->copy()->startOfDay();
        if ($origin === null) {
            return ['series' => null, 'eta' => null, 'eta_label' => null];
        }

        $xs = [];
        $ys = [];
        foreach ($movements as $movement) {
            if ($movement->recorded_at === null) {
                continue;
            }
            $xs[] = $origin->diffInSeconds($movement->recorded_at, false) / 86400;
            $ys[] = (float) $movement->quantity_after;
        }

        if (count($xs) < 2) {
            return ['series' => null, 'eta' => null, 'eta_label' => null];
        }

        [$slope, $intercept] = $this->linearRegression($xs, $ys);

        // Declining stock only projects a useful reorder ETA.
        if ($slope >= -0.0001) {
            return [
                'series' => [
                    'slope_per_day' => $slope,
                    'intercept' => $intercept,
                    'projection' => $this->projectionPoints($movements, $slope, $intercept, $origin, null),
                ],
                'eta' => null,
                'eta_label' => 'Usage not declining — no reorder ETA',
            ];
        }

        $daysToMin = ($minStockLevel - $intercept) / $slope;
        $eta = null;
        $etaLabel = null;

        if ($daysToMin >= 0) {
            $etaDate = $origin->copy()->addSeconds((int) round($daysToMin * 86400));
            if ($etaDate->greaterThanOrEqualTo(now()->subDay())) {
                $eta = $etaDate->toDateString();
                $etaLabel = 'Est. hits min on '.$etaDate->format('Y-m-d');
            } else {
                $etaLabel = 'Already at or below min (trend)';
            }
        }

        return [
            'series' => [
                'slope_per_day' => $slope,
                'intercept' => $intercept,
                'projection' => $this->projectionPoints($movements, $slope, $intercept, $origin, $eta ? Carbon::parse($eta) : null),
            ],
            'eta' => $eta,
            'eta_label' => $etaLabel,
        ];
    }

    /**
     * @param  list<float>  $xs
     * @param  list<float>  $ys
     * @return array{0: float, 1: float}
     */
    protected function linearRegression(array $xs, array $ys): array
    {
        $n = count($xs);
        $sumX = array_sum($xs);
        $sumY = array_sum($ys);
        $sumXY = 0.0;
        $sumXX = 0.0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += $xs[$i] * $ys[$i];
            $sumXX += $xs[$i] * $xs[$i];
        }

        $denominator = ($n * $sumXX) - ($sumX * $sumX);
        if (abs($denominator) < 1e-9) {
            return [0.0, $sumY / max(1, $n)];
        }

        $slope = (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
        $intercept = ($sumY - ($slope * $sumX)) / $n;

        return [$slope, $intercept];
    }

    /**
     * @param  Collection<int, StockMovement>  $movements
     * @return list<array{x: string, y: float}>
     */
    protected function projectionPoints(
        Collection $movements,
        float $slope,
        float $intercept,
        Carbon $origin,
        ?Carbon $eta,
    ): array {
        $first = $movements->first()->recorded_at;
        $last = $movements->last()->recorded_at;
        if ($first === null || $last === null) {
            return [];
        }

        $end = $eta && $eta->greaterThan($last) ? $eta : $last->copy()->addDays(7);
        $startDays = $origin->diffInSeconds($first, false) / 86400;
        $endDays = $origin->diffInSeconds($end, false) / 86400;

        return [
            [
                'x' => $first->toIso8601String(),
                'y' => round($intercept + ($slope * $startDays), 2),
            ],
            [
                'x' => $end->toIso8601String(),
                'y' => round(max(0, $intercept + ($slope * $endDays)), 2),
            ],
        ];
    }
}
