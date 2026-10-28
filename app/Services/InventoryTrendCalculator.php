<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class InventoryTrendCalculator
{
    /**
     * Linear-regression reorder ETA from a chronological series of quantity snapshots.
     *
     * Expects each item to expose `recorded_at` (Carbon|null) and `quantity_after` (numeric).
     *
     * @param  Collection<int, object>  $movements
     * @return array{series: array{slope_per_day: float, intercept: float, projection: list<array{x: string, y: float}>}|null, eta: string|null, eta_label: string|null}
     */
    public function compute(Collection $movements, ?int $minLevel): array
    {
        $movements = $this->sinceLastRestock($movements);

        if ($movements->count() < 2 || $minLevel === null) {
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

        // #region agent log
        $debugProjectionPreview = $this->projectionPoints($movements, $slope, $intercept, $origin, null);
        $debugRestocks = 0;
        foreach ($movements as $debugMovement) {
            $debugType = $debugMovement->type->value ?? null;
            if ($debugType === 'restock') {
                $debugRestocks++;
            }
        }
        file_put_contents(base_path('debug-ff4d0e.log'), json_encode([
            'sessionId' => 'ff4d0e',
            'runId' => 'pre-fix',
            'hypothesisId' => 'H2-H4',
            'location' => 'InventoryTrendCalculator.php:compute',
            'message' => 'trend fit vs actual counts',
            'data' => [
                'movementCount' => count($xs),
                'restockCount' => $debugRestocks,
                'slopePerDay' => $slope,
                'intercept' => $intercept,
                'actualYMin' => min($ys),
                'actualYMax' => max($ys),
                'projection' => $debugProjectionPreview,
                'minLevel' => $minLevel,
                'daysToMin' => $slope < -0.0001 ? ($minLevel - $intercept) / $slope : null,
                'firstRecordedAt' => $movements->first()->recorded_at?->toIso8601String(),
                'lastRecordedAt' => $movements->last()->recorded_at?->toIso8601String(),
            ],
            'timestamp' => (int) round(microtime(true) * 1000),
        ]).PHP_EOL, FILE_APPEND);
        // #endregion

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

        $daysToMin = ($minLevel - $intercept) / $slope;
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
     * @param  Collection<int, object>  $movements
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

        $startDays = $origin->diffInSeconds($first, false) / 86400;
        $startY = max(0, $intercept + ($slope * $startDays));

        if ($slope < -0.0001) {
            $daysToZero = (0 - $intercept) / $slope;
            $end = $daysToZero > $startDays
                ? $origin->copy()->addSeconds((int) round($daysToZero * 86400))
                : $first->copy();
            $endY = 0.0;
        } else {
            $end = $last->copy();
            $endDays = $origin->diffInSeconds($end, false) / 86400;
            $endY = max(0, $intercept + ($slope * $endDays));
        }

        $points = [
            [
                'x' => $first->toIso8601String(),
                'y' => round($startY, 2),
            ],
            [
                'x' => $end->toIso8601String(),
                'y' => round($endY, 2),
            ],
        ];

        // #region agent log
        file_put_contents(base_path('debug-ff4d0e.log'), json_encode([
            'sessionId' => 'ff4d0e',
            'runId' => 'post-fix',
            'hypothesisId' => 'H3-H4',
            'location' => 'InventoryTrendCalculator.php:projectionPoints',
            'message' => 'trend segment ends at zero without extending past restock',
            'data' => [
                'segmentStart' => $first->toIso8601String(),
                'segmentLastEvent' => $last->toIso8601String(),
                'projectionEnd' => $end->toIso8601String(),
                'projectionEndY' => round($endY, 2),
                'slopePerDay' => $slope,
                'ignoredEta' => $eta?->toIso8601String(),
            ],
            'timestamp' => (int) round(microtime(true) * 1000),
        ]).PHP_EOL, FILE_APPEND);
        // #endregion

        return $points;
    }

    /**
     * Fit usage only after the latest restock so a delivery does not bend the previous decline.
     *
     * @param  Collection<int, object>  $movements
     * @return Collection<int, object>
     */
    protected function sinceLastRestock(Collection $movements): Collection
    {
        $lastRestockIndex = null;

        foreach ($movements->values() as $index => $movement) {
            $type = $movement->type->value ?? null;
            if ($type === 'restock') {
                $lastRestockIndex = $index;
            }
        }

        if ($lastRestockIndex === null) {
            return $movements->values();
        }

        return $movements->values()->slice($lastRestockIndex)->values();
    }
}
