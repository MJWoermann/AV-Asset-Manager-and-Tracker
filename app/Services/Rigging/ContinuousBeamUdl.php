<?php

namespace App\Services\Rigging;

/**
 * Equal-span continuous beam UDL reaction coefficients.
 *
 * For N supports (N-1 equal spans) under uniform load, solve Clapeyron three-moment
 * equations with simple end moments = 0, then recover support reactions.
 *
 * Documented Excel-compatible fixtures for N=1…8 are asserted in unit tests;
 * N>8 uses the same continuous-beam solver.
 */
class ContinuousBeamUdl
{
    /**
     * Reaction coefficients that sum to 1.0 for N pick points.
     *
     * @return list<float>
     */
    public function coefficients(int $n): array
    {
        if ($n < 1) {
            throw new \InvalidArgumentException('Point count must be at least 1.');
        }

        if ($n === 1) {
            return [1.0];
        }

        if ($n === 2) {
            return [0.5, 0.5];
        }

        // Unit span length L=1, UDL w=1 → total load W = (n-1)*w*L
        $spans = $n - 1;
        $w = 1.0;
        $L = 1.0;
        $W = $spans * $w * $L;

        // Interior support moments M[1]…M[n-2] (0-based support indices 1..n-2)
        $interiorCount = $n - 2;
        $moments = array_fill(0, $n, 0.0);

        if ($interiorCount > 0) {
            // Build (interiorCount)x(interiorCount) system: M_{i-1} + 4 M_i + M_{i+1} = -w L^2 / 2
            $rhsValue = -($w * $L * $L) / 2.0;
            $matrix = [];
            $rhs = [];

            for ($row = 0; $row < $interiorCount; $row++) {
                $matrix[$row] = array_fill(0, $interiorCount, 0.0);
                $matrix[$row][$row] = 4.0;
                if ($row > 0) {
                    $matrix[$row][$row - 1] = 1.0;
                }
                if ($row < $interiorCount - 1) {
                    $matrix[$row][$row + 1] = 1.0;
                }
                $rhs[$row] = $rhsValue;
            }

            $solved = $this->solveLinear($matrix, $rhs);
            for ($i = 0; $i < $interiorCount; $i++) {
                $moments[$i + 1] = $solved[$i];
            }
        }

        $reactions = array_fill(0, $n, 0.0);

        // Moments from Clapeyron (hogging negative with RHS = -wL²/2). End shears:
        // R_left = wL/2 + (Mj - Mi)/L, R_right = wL/2 + (Mi - Mj)/L.
        for ($s = 0; $s < $spans; $s++) {
            $Mi = $moments[$s];
            $Mj = $moments[$s + 1];
            $leftShear = ($w * $L) / 2.0 + ($Mj - $Mi) / $L;
            $rightShear = ($w * $L) / 2.0 + ($Mi - $Mj) / $L;
            $reactions[$s] += $leftShear;
            $reactions[$s + 1] += $rightShear;
        }

        return array_map(fn (float $r): float => $r / $W, $reactions);
    }

    /**
     * @param  list<list<float>>  $matrix
     * @param  list<float>  $rhs
     * @return list<float>
     */
    protected function solveLinear(array $matrix, array $rhs): array
    {
        $n = count($rhs);
        $a = $matrix;
        $b = $rhs;

        for ($col = 0; $col < $n; $col++) {
            $pivot = $col;
            for ($row = $col + 1; $row < $n; $row++) {
                if (abs($a[$row][$col]) > abs($a[$pivot][$col])) {
                    $pivot = $row;
                }
            }

            if (abs($a[$pivot][$col]) < 1e-12) {
                throw new \RuntimeException('Singular continuous-beam system.');
            }

            if ($pivot !== $col) {
                [$a[$col], $a[$pivot]] = [$a[$pivot], $a[$col]];
                [$b[$col], $b[$pivot]] = [$b[$pivot], $b[$col]];
            }

            $div = $a[$col][$col];
            for ($j = $col; $j < $n; $j++) {
                $a[$col][$j] /= $div;
            }
            $b[$col] /= $div;

            for ($row = 0; $row < $n; $row++) {
                if ($row === $col) {
                    continue;
                }
                $factor = $a[$row][$col];
                for ($j = $col; $j < $n; $j++) {
                    $a[$row][$j] -= $factor * $a[$col][$j];
                }
                $b[$row] -= $factor * $b[$col];
            }
        }

        return $b;
    }
}
