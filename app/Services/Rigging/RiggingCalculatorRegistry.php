<?php

namespace App\Services\Rigging;

use App\Models\ItemType;
use App\Support\RiggingCalculators;
use InvalidArgumentException;

class RiggingCalculatorRegistry
{
    public function __construct(
        private ContinuousBeamUdl $continuousBeamUdl = new ContinuousBeamUdl,
    ) {}

    /**
     * @return array<string, string>
     */
    public function catalog(): array
    {
        return RiggingCalculators::definitions();
    }

    public function assertValid(string $calculator): void
    {
        if (! RiggingCalculators::isValid($calculator)) {
            throw new InvalidArgumentException("Unknown calculator [{$calculator}].");
        }
    }

    /**
     * Validate inputs and return server-computed results (plus normalized inputs snapshot).
     *
     * @param  array<string, mixed>  $inputs
     * @return array{inputs: array<string, mixed>, results: array<string, mixed>, units: array<string, mixed>}
     */
    public function compute(string $calculator, array $inputs): array
    {
        $this->assertValid($calculator);

        return match ($calculator) {
            'two_point_hang' => $this->twoPointHang($inputs),
            'lever' => $this->lever($inputs),
            'bridle_two_leg' => $this->bridleTwoLeg($inputs),
            'bridle_multi_leg' => $this->bridleMultiLeg($inputs),
            'sling_angle_factor' => $this->slingAngleFactor($inputs),
            'dead_hang' => $this->deadHang($inputs),
            'beam_point_load' => $this->beamPointLoad($inputs),
            'udl_multi_point' => $this->udlMultiPoint($inputs),
            'projector_lens' => $this->projectorLens($inputs),
            'unit_convert' => $this->unitConvert($inputs),
            default => throw new InvalidArgumentException("Unknown calculator [{$calculator}]."),
        };
    }

    /**
     * @param  array<string, mixed>  $inputs
     * @return array{inputs: array<string, mixed>, results: array<string, mixed>, units: array<string, mixed>}
     */
    public function twoPointHang(array $inputs): array
    {
        $W = $this->positiveFloat($inputs, 'W');
        $L = $this->positiveFloat($inputs, 'L');
        $x = $this->floatInRange($inputs, 'x', 0, $L);

        $RA = $W * ($L - $x) / $L;
        $RB = $W * $x / $L;

        return [
            'inputs' => ['W' => $W, 'L' => $L, 'x' => $x],
            'results' => [
                'RA' => round($RA, 4),
                'RB' => round($RB, 4),
            ],
            'units' => ['force' => 'kg', 'length' => 'm'],
        ];
    }

    /**
     * @param  array<string, mixed>  $inputs
     * @return array{inputs: array<string, mixed>, results: array<string, mixed>, units: array<string, mixed>}
     */
    public function lever(array $inputs): array
    {
        $class = strtoupper((string) ($inputs['class'] ?? 'I'));
        if (! in_array($class, ['I', 'II', 'III'], true)) {
            throw new InvalidArgumentException('Lever class must be I, II, or III.');
        }

        $Fr = $this->positiveFloat($inputs, 'Fr');
        $dr = $this->positiveFloat($inputs, 'dr');
        $de = $this->positiveFloat($inputs, 'de');

        $g = RiggingCalculators::G;
        $FrN = $Fr * $g;
        $Fe = ($Fr * $dr) / $de;
        $FeN = $Fe * $g;

        $fulcrumKg = match ($class) {
            'I' => $Fe + $Fr,
            'II' => $Fr - $Fe,
            'III' => $Fe - $Fr,
        };

        $L = match ($class) {
            'I' => $de + $dr,
            'II', 'III' => max($de, $dr),
        };

        $ma = $Fe > 0 ? $Fr / $Fe : 0.0;

        return [
            'inputs' => [
                'class' => $class,
                'Fr' => $Fr,
                'dr' => $dr,
                'de' => $de,
            ],
            'results' => [
                'Fe_kg' => round($Fe, 4),
                'Fe_N' => round($FeN, 4),
                'fulcrum_kg' => round($fulcrumKg, 4),
                'fulcrum_N' => round($fulcrumKg * $g, 4),
                'Fr_N' => round($FrN, 4),
                'L' => round($L, 4),
                'mechanical_advantage' => round($ma, 4),
            ],
            'units' => ['force' => 'kg', 'length' => 'm', 'g' => $g],
        ];
    }

    /**
     * Two-leg bridle: apex load W, horizontal span between anchors S, vertical drop H.
     * Legs share load by geometry: T = (W/2) / cos(θ) for symmetric; asymmetric uses horizontal offset.
     *
     * @param  array<string, mixed>  $inputs
     * @return array{inputs: array<string, mixed>, results: array<string, mixed>, units: array<string, mixed>}
     */
    public function bridleTwoLeg(array $inputs): array
    {
        $W = $this->positiveFloat($inputs, 'W');
        $span = $this->positiveFloat($inputs, 'span');
        $drop = $this->positiveFloat($inputs, 'drop');
        $offset = isset($inputs['offset']) ? (float) $inputs['offset'] : $span / 2;
        if ($offset < 0 || $offset > $span) {
            throw new InvalidArgumentException('Offset must be between 0 and span.');
        }

        $a = $offset;
        $b = $span - $offset;
        $len1 = sqrt($a * $a + $drop * $drop);
        $len2 = sqrt($b * $b + $drop * $drop);

        // Vertical equilibrium: T1*(drop/L1) + T2*(drop/L2) = W
        // Horizontal: T1*(a/L1) = T2*(b/L2)
        $cos1 = $drop / $len1;
        $cos2 = $drop / $len2;
        $sin1 = $a / $len1;
        $sin2 = $b / $len2;

        // From horizontal: T1 = T2 * (sin2/sin1) when sin1 != 0
        if ($sin1 < 1e-9 && $sin2 < 1e-9) {
            $T1 = $W / 2;
            $T2 = $W / 2;
        } elseif ($sin1 < 1e-9) {
            $T1 = 0.0;
            $T2 = $W / $cos2;
        } elseif ($sin2 < 1e-9) {
            $T2 = 0.0;
            $T1 = $W / $cos1;
        } else {
            $ratio = $sin2 / $sin1; // T1 = ratio * T2
            $T2 = $W / ($ratio * $cos1 + $cos2);
            $T1 = $ratio * $T2;
        }

        $angle1 = rad2deg(atan2($a, $drop));
        $angle2 = rad2deg(atan2($b, $drop));

        return [
            'inputs' => [
                'W' => $W,
                'span' => $span,
                'drop' => $drop,
                'offset' => $offset,
            ],
            'results' => [
                'T1' => round($T1, 4),
                'T2' => round($T2, 4),
                'leg1_length' => round($len1, 4),
                'leg2_length' => round($len2, 4),
                'angle1_from_vertical_deg' => round($angle1, 4),
                'angle2_from_vertical_deg' => round($angle2, 4),
            ],
            'units' => ['force' => 'kg', 'length' => 'm'],
        ];
    }

    /**
     * Symmetric multi-leg bridle estimate: equal share / cos(θ).
     *
     * @param  array<string, mixed>  $inputs
     * @return array{inputs: array<string, mixed>, results: array<string, mixed>, units: array<string, mixed>}
     */
    public function bridleMultiLeg(array $inputs): array
    {
        $W = $this->positiveFloat($inputs, 'W');
        $legs = (int) ($inputs['legs'] ?? 0);
        if ($legs < 2 || $legs > 16) {
            throw new InvalidArgumentException('Leg count must be between 2 and 16.');
        }
        $angle = $this->floatInRange($inputs, 'angle_from_vertical_deg', 0, 80);
        $factor = 1 / cos(deg2rad($angle));
        $perLeg = ($W / $legs) * $factor;

        return [
            'inputs' => [
                'W' => $W,
                'legs' => $legs,
                'angle_from_vertical_deg' => $angle,
            ],
            'results' => [
                'angle_factor' => round($factor, 4),
                'tension_per_leg' => round($perLeg, 4),
                'total_tension' => round($perLeg * $legs, 4),
            ],
            'units' => ['force' => 'kg'],
        ];
    }

    /**
     * @param  array<string, mixed>  $inputs
     * @return array{inputs: array<string, mixed>, results: array<string, mixed>, units: array<string, mixed>}
     */
    public function slingAngleFactor(array $inputs): array
    {
        $W = $this->positiveFloat($inputs, 'W');
        $angle = $this->floatInRange($inputs, 'angle_from_vertical_deg', 0, 80);
        $wll = isset($inputs['wll']) ? $this->positiveFloat($inputs, 'wll') : null;
        $factor = 1 / cos(deg2rad($angle));
        $tension = $W * $factor;

        $results = [
            'angle_factor' => round($factor, 4),
            'tension' => round($tension, 4),
        ];

        if ($wll !== null) {
            $results['wll'] = $wll;
            $results['utilization_pct'] = round(($tension / $wll) * 100, 2);
            $results['within_wll'] = $tension <= $wll;
        }

        return [
            'inputs' => array_filter([
                'W' => $W,
                'angle_from_vertical_deg' => $angle,
                'wll' => $wll,
            ], fn ($v) => $v !== null),
            'results' => $results,
            'units' => ['force' => 'kg'],
        ];
    }

    /**
     * @param  array<string, mixed>  $inputs
     * @return array{inputs: array<string, mixed>, results: array<string, mixed>, units: array<string, mixed>}
     */
    public function deadHang(array $inputs): array
    {
        $W = $this->positiveFloat($inputs, 'W');
        $wll = $this->positiveFloat($inputs, 'wll');
        $utilization = ($W / $wll) * 100;

        return [
            'inputs' => ['W' => $W, 'wll' => $wll],
            'results' => [
                'utilization_pct' => round($utilization, 2),
                'within_wll' => $W <= $wll,
                'headroom_kg' => round($wll - $W, 4),
            ],
            'units' => ['force' => 'kg'],
        ];
    }

    /**
     * Simply supported beam with discrete point loads.
     * loads: [{x, W}, ...] where x is distance from left support A.
     *
     * @param  array<string, mixed>  $inputs
     * @return array{inputs: array<string, mixed>, results: array<string, mixed>, units: array<string, mixed>}
     */
    public function beamPointLoad(array $inputs): array
    {
        $L = $this->positiveFloat($inputs, 'L');
        $loads = $inputs['loads'] ?? null;
        if (! is_array($loads) || $loads === []) {
            throw new InvalidArgumentException('At least one point load is required.');
        }

        $normalized = [];
        $totalW = 0.0;
        $momentAboutA = 0.0;

        foreach ($loads as $i => $load) {
            if (! is_array($load)) {
                throw new InvalidArgumentException("Load {$i} is invalid.");
            }
            $W = $this->positiveFloat($load, 'W');
            $x = $this->floatInRange($load, 'x', 0, $L);
            $normalized[] = ['W' => $W, 'x' => $x];
            $totalW += $W;
            $momentAboutA += $W * $x;
        }

        $RB = $momentAboutA / $L;
        $RA = $totalW - $RB;

        return [
            'inputs' => ['L' => $L, 'loads' => $normalized],
            'results' => [
                'RA' => round($RA, 4),
                'RB' => round($RB, 4),
                'total_W' => round($totalW, 4),
            ],
            'units' => ['force' => 'kg', 'length' => 'm'],
        ];
    }

    /**
     * @param  array<string, mixed>  $inputs
     * @return array{inputs: array<string, mixed>, results: array<string, mixed>, units: array<string, mixed>}
     */
    public function udlMultiPoint(array $inputs): array
    {
        $n = (int) ($inputs['point_count'] ?? 0);
        if ($n < 1 || $n > 32) {
            throw new InvalidArgumentException('Point count must be between 1 and 32.');
        }

        $mode = (string) ($inputs['distribution_mode'] ?? 'equal');
        if (! in_array($mode, ['continuous_beam', 'equal', 'custom'], true)) {
            throw new InvalidArgumentException('Invalid distribution mode.');
        }

        $hoistSelfWeight = max(0.0, (float) ($inputs['hoist_self_weight_kg'] ?? 0));
        $lines = $inputs['weight_lines'] ?? [];
        if (! is_array($lines)) {
            throw new InvalidArgumentException('weight_lines must be an array.');
        }

        $resolvedLines = [];
        $totalLoad = 0.0;

        foreach ($lines as $i => $line) {
            if (! is_array($line)) {
                throw new InvalidArgumentException("Weight line {$i} is invalid.");
            }

            $qty = max(0.0, (float) ($line['quantity'] ?? 1));
            $itemTypeId = isset($line['item_type_id']) && $line['item_type_id'] !== '' && $line['item_type_id'] !== null
                ? (int) $line['item_type_id']
                : null;

            $label = trim((string) ($line['label'] ?? ''));
            $unitWeight = isset($line['weight_kg']) && $line['weight_kg'] !== '' && $line['weight_kg'] !== null
                ? (float) $line['weight_kg']
                : null;

            if ($itemTypeId !== null) {
                $type = ItemType::query()->find($itemTypeId);
                if (! $type || ! $type->supports_rigging_calculator) {
                    throw new InvalidArgumentException("Item type {$itemTypeId} is not enabled for the Rigging calculator.");
                }
                if ($label === '') {
                    $label = $type->name;
                }
                if ($unitWeight === null) {
                    if ($type->rigging_weight_kg === null) {
                        throw new InvalidArgumentException("Item type [{$type->name}] has no catalogue weight; enter weight manually.");
                    }
                    $unitWeight = (float) $type->rigging_weight_kg;
                }
            }

            if ($unitWeight === null || $unitWeight < 0) {
                throw new InvalidArgumentException("Weight line {$i} requires a non-negative weight_kg.");
            }

            if ($label === '') {
                $label = 'Line '.($i + 1);
            }

            $lineTotal = $unitWeight * $qty;
            $totalLoad += $lineTotal;
            $resolvedLines[] = [
                'label' => $label,
                'weight_kg' => round($unitWeight, 4),
                'quantity' => $qty,
                'line_total_kg' => round($lineTotal, 4),
                'item_type_id' => $itemTypeId,
            ];
        }

        $coefficients = match ($mode) {
            'equal' => array_fill(0, $n, 1 / $n),
            'continuous_beam' => $this->continuousBeamUdl->coefficients($n),
            'custom' => $this->customPercents($inputs['custom_percents'] ?? [], $n),
        };

        $points = [];
        for ($i = 0; $i < $n; $i++) {
            $share = $totalLoad * $coefficients[$i];
            $points[] = [
                'index' => $i + 1,
                'coefficient' => round($coefficients[$i], 6),
                'share_kg' => round($share, 4),
                'hoist_self_weight_kg' => round($hoistSelfWeight, 4),
                'total_kg' => round($share + $hoistSelfWeight, 4),
            ];
        }

        $grand = $totalLoad + ($hoistSelfWeight * $n);

        return [
            'inputs' => [
                'point_count' => $n,
                'distribution_mode' => $mode,
                'hoist_self_weight_kg' => $hoistSelfWeight,
                'weight_lines' => $resolvedLines,
                'custom_percents' => $mode === 'custom' ? array_map(fn ($c) => round($c * 100, 4), $coefficients) : null,
            ],
            'results' => [
                'total_load_kg' => round($totalLoad, 4),
                'points' => $points,
                'grand_total_kg' => round($grand, 4),
            ],
            'units' => ['force' => 'kg'],
        ];
    }

    /**
     * @param  array<string, mixed>  $inputs
     * @return array{inputs: array<string, mixed>, results: array<string, mixed>, units: array<string, mixed>}
     */
    public function projectorLens(array $inputs): array
    {
        $mode = (string) ($inputs['mode'] ?? '');
        $aspect = (string) ($inputs['aspect'] ?? '16:9');
        $aspectRatio = $this->aspectRatio($aspect, $inputs);

        $throwRatio = isset($inputs['throw_ratio']) ? (float) $inputs['throw_ratio'] : null;
        $throwDistance = isset($inputs['throw_distance']) ? (float) $inputs['throw_distance'] : null;
        $imageWidth = isset($inputs['image_width']) ? (float) $inputs['image_width'] : null;
        $zoomMin = isset($inputs['zoom_min']) ? (float) $inputs['zoom_min'] : null;
        $zoomMax = isset($inputs['zoom_max']) ? (float) $inputs['zoom_max'] : null;

        $results = [
            'aspect_ratio' => $aspectRatio,
        ];

        switch ($mode) {
            case 'width_from_ratio_distance':
                if ($throwRatio === null || $throwRatio <= 0 || $throwDistance === null || $throwDistance <= 0) {
                    throw new InvalidArgumentException('throw_ratio and throw_distance are required.');
                }
                $imageWidth = $throwDistance / $throwRatio;
                break;
            case 'distance_from_ratio_width':
                if ($throwRatio === null || $throwRatio <= 0 || $imageWidth === null || $imageWidth <= 0) {
                    throw new InvalidArgumentException('throw_ratio and image_width are required.');
                }
                $throwDistance = $throwRatio * $imageWidth;
                break;
            case 'ratio_from_distance_width':
                if ($throwDistance === null || $throwDistance <= 0 || $imageWidth === null || $imageWidth <= 0) {
                    throw new InvalidArgumentException('throw_distance and image_width are required.');
                }
                $throwRatio = $throwDistance / $imageWidth;
                break;
            default:
                throw new InvalidArgumentException('Invalid projector lens mode.');
        }

        $imageHeight = $imageWidth / $aspectRatio;

        $results['throw_ratio'] = round((float) $throwRatio, 4);
        $results['throw_distance'] = round((float) $throwDistance, 4);
        $results['image_width'] = round((float) $imageWidth, 4);
        $results['image_height'] = round($imageHeight, 4);

        if ($zoomMin !== null && $zoomMax !== null && $zoomMin > 0 && $zoomMax >= $zoomMin) {
            if ($throwDistance !== null) {
                $results['zoom_width_min'] = round($throwDistance / $zoomMax, 4);
                $results['zoom_width_max'] = round($throwDistance / $zoomMin, 4);
            }
            if ($imageWidth !== null) {
                $results['zoom_distance_min'] = round($zoomMin * $imageWidth, 4);
                $results['zoom_distance_max'] = round($zoomMax * $imageWidth, 4);
            }
        }

        return [
            'inputs' => [
                'mode' => $mode,
                'aspect' => $aspect,
                'aspect_ratio' => $aspectRatio,
                'throw_ratio' => $throwRatio,
                'throw_distance' => $throwDistance,
                'image_width' => $imageWidth,
                'zoom_min' => $zoomMin,
                'zoom_max' => $zoomMax,
            ],
            'results' => $results,
            'units' => ['length' => 'm'],
        ];
    }

    /**
     * @param  array<string, mixed>  $inputs
     * @return array{inputs: array<string, mixed>, results: array<string, mixed>, units: array<string, mixed>}
     */
    public function unitConvert(array $inputs): array
    {
        $value = (float) ($inputs['value'] ?? 0);
        $from = (string) ($inputs['from'] ?? '');
        $to = (string) ($inputs['to'] ?? '');

        $factorsToSi = [
            'kg' => 1.0,
            'lb' => 0.45359237,
            'm' => 1.0,
            'ft' => 0.3048,
            'kN' => 1000.0,
            'lbf' => 4.4482216153,
        ];

        $families = [
            'kg' => 'mass',
            'lb' => 'mass',
            'm' => 'length',
            'ft' => 'length',
            'kN' => 'force',
            'lbf' => 'force',
        ];

        if (! isset($factorsToSi[$from], $factorsToSi[$to])) {
            throw new InvalidArgumentException('Unsupported unit.');
        }
        if ($families[$from] !== $families[$to]) {
            throw new InvalidArgumentException('Cannot convert between different unit families.');
        }

        $si = $value * $factorsToSi[$from];
        $converted = $si / $factorsToSi[$to];

        return [
            'inputs' => ['value' => $value, 'from' => $from, 'to' => $to],
            'results' => ['value' => round($converted, 6)],
            'units' => ['from' => $from, 'to' => $to, 'family' => $families[$from]],
        ];
    }

    /**
     * @param  array<int, mixed>  $percents
     * @return list<float>
     */
    protected function customPercents(array $percents, int $n): array
    {
        if (count($percents) !== $n) {
            throw new InvalidArgumentException("custom_percents must have exactly {$n} values.");
        }

        $values = [];
        $sum = 0.0;
        foreach ($percents as $p) {
            $v = (float) $p;
            if ($v < 0) {
                throw new InvalidArgumentException('Percentages must be non-negative.');
            }
            $values[] = $v;
            $sum += $v;
        }

        if (abs($sum - 100.0) > 0.05) {
            throw new InvalidArgumentException('Custom percentages must sum to 100%.');
        }

        return array_map(fn (float $v): float => $v / 100.0, $values);
    }

    /**
     * @param  array<string, mixed>  $inputs
     */
    protected function aspectRatio(string $aspect, array $inputs): float
    {
        return match ($aspect) {
            '16:9' => 16 / 9,
            '16:10' => 16 / 10,
            '4:3' => 4 / 3,
            'custom' => $this->positiveFloat($inputs, 'custom_aspect'),
            default => throw new InvalidArgumentException('Invalid aspect ratio.'),
        };
    }

    /**
     * @param  array<string, mixed>  $inputs
     */
    protected function positiveFloat(array $inputs, string $key): float
    {
        if (! isset($inputs[$key]) || $inputs[$key] === '' || $inputs[$key] === null) {
            throw new InvalidArgumentException("{$key} is required.");
        }
        $value = (float) $inputs[$key];
        if ($value <= 0) {
            throw new InvalidArgumentException("{$key} must be greater than zero.");
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $inputs
     */
    protected function floatInRange(array $inputs, string $key, float $min, float $max): float
    {
        if (! isset($inputs[$key]) || $inputs[$key] === '' || $inputs[$key] === null) {
            throw new InvalidArgumentException("{$key} is required.");
        }
        $value = (float) $inputs[$key];
        if ($value < $min || $value > $max) {
            throw new InvalidArgumentException("{$key} must be between {$min} and {$max}.");
        }

        return $value;
    }
}
