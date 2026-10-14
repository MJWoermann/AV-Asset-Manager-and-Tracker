<?php

namespace App\Support;

class ShelfTagPaperPresets
{
    public const DEFAULT = 'continuous_62';

    /**
     * Brother QL / DK paper presets in millimetres.
     *
     * @return array<string, array{
     *     label: string,
     *     width_mm: float,
     *     height_mm: float|null,
     *     continuous: bool,
     *     printers: list<string>,
     *     notes: string
     * }>
     */
    public static function all(): array
    {
        return [
            'continuous_62' => [
                'label' => '62 mm continuous',
                'width_mm' => 62.0,
                'height_mm' => null,
                'continuous' => true,
                'printers' => ['QL-700', 'QL-1100'],
                'notes' => 'Default. Set label length in the layout (e.g. 29–100 mm).',
            ],
            'continuous_102' => [
                'label' => '102 mm continuous (wide)',
                'width_mm' => 102.0,
                'height_mm' => null,
                'continuous' => true,
                'printers' => ['QL-1100'],
                'notes' => 'QL-1100 only.',
            ],
            'diecut_29x90' => [
                'label' => '29 × 90 mm (address)',
                'width_mm' => 29.0,
                'height_mm' => 90.0,
                'continuous' => false,
                'printers' => ['QL-700', 'QL-1100'],
                'notes' => 'DK address label.',
            ],
            'diecut_38x90' => [
                'label' => '38 × 90 mm (large address)',
                'width_mm' => 38.0,
                'height_mm' => 90.0,
                'continuous' => false,
                'printers' => ['QL-700', 'QL-1100'],
                'notes' => 'DK large address label.',
            ],
            'diecut_62x100' => [
                'label' => '62 × 100 mm (shipping)',
                'width_mm' => 62.0,
                'height_mm' => 100.0,
                'continuous' => false,
                'printers' => ['QL-700', 'QL-1100'],
                'notes' => 'DK shipping label.',
            ],
            'diecut_102x152' => [
                'label' => '102 × 152 mm (large shipping)',
                'width_mm' => 102.0,
                'height_mm' => 152.0,
                'continuous' => false,
                'printers' => ['QL-1100'],
                'notes' => 'QL-1100 large shipping (~103 × 164 printable).',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function isValid(string $key): bool
    {
        return array_key_exists($key, self::all());
    }

    /**
     * @return array{
     *     label: string,
     *     width_mm: float,
     *     height_mm: float|null,
     *     continuous: bool,
     *     printers: list<string>,
     *     notes: string
     * }|null
     */
    public static function get(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    public static function isContinuous(string $key): bool
    {
        return (bool) (self::get($key)['continuous'] ?? false);
    }

    public static function defaultWidthMm(string $key): float
    {
        return (float) (self::get($key)['width_mm'] ?? 62.0);
    }

    public static function defaultHeightMm(string $key): float
    {
        $preset = self::get($key);

        if ($preset === null) {
            return 40.0;
        }

        if ($preset['continuous']) {
            return 40.0;
        }

        return (float) $preset['height_mm'];
    }
}
