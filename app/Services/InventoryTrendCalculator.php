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
