<?php

namespace App\Support;

class ShelfTagPaperPresets
{
    public const DEFAULT = 'continuous_62';

    /**
     * Brother QL continuous cut length (mm).
     * 12.7 mm at 62 mm tape width and narrower; 25.4 mm when wider.
     */
    public const CONTINUOUS_NARROW_MAX_WIDTH_MM = 62.0;

    public const CONTINUOUS_LENGTH_MIN_NARROW_MM = 12.7;

    public const CONTINUOUS_LENGTH_MIN_WIDE_MM = 25.4;

    public const CONTINUOUS_LENGTH_MAX_MM = 300.0;

    public const LENGTH_MODE_FIXED = 'fixed';

    public const LENGTH_MODE_AUTO = 'auto';

    public const BLEED_MAX_MM = 50.0;

    /** @return list<string> */
    public static function lengthModes(): array
    {
        return [self::LENGTH_MODE_FIXED, self::LENGTH_MODE_AUTO];
    }

    /**
     * Default layout-editor viewport settings (shared on the template).
     *
     * @return array{show_grid: bool, snap_to_grid: bool, grid_size_mm: float, show_bleed: bool, bleed_top_mm: float, bleed_bottom_mm: float, bleed_left_mm: float, bleed_right_mm: float}
     */
    public static function defaultEditorSettings(): array
    {
        return [
            'show_grid' => true,
            'snap_to_grid' => false,
            'grid_size_mm' => 5.0,
            'show_bleed' => true,
            'bleed_top_mm' => 3.0,
            'bleed_bottom_mm' => 3.0,
            'bleed_left_mm' => 2.0,
            'bleed_right_mm' => 2.0,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $settings
     * @return array{show_grid: bool, snap_to_grid: bool, grid_size_mm: float, show_bleed: bool, bleed_top_mm: float, bleed_bottom_mm: float, bleed_left_mm: float, bleed_right_mm: float}
     */
    public static function normalizeEditorSettings(?array $settings): array
    {
        $defaults = self::defaultEditorSettings();
        if ($settings === null) {
            return $defaults;
        }

        $gridSize = (float) ($settings['grid_size_mm'] ?? $defaults['grid_size_mm']);
        if (! in_array($gridSize, [2.5, 5.0, 10.0], true)) {
            $gridSize = $defaults['grid_size_mm'];
        }

        return [
            'show_grid' => (bool) ($settings['show_grid'] ?? $defaults['show_grid']),
            'snap_to_grid' => (bool) ($settings['snap_to_grid'] ?? $defaults['snap_to_grid']),
            'grid_size_mm' => $gridSize,
            'show_bleed' => (bool) ($settings['show_bleed'] ?? $defaults['show_bleed']),
            'bleed_top_mm' => self::clampBleed($settings['bleed_top_mm'] ?? null, $defaults['bleed_top_mm']),
            'bleed_bottom_mm' => self::clampBleed($settings['bleed_bottom_mm'] ?? null, $defaults['bleed_bottom_mm']),
            'bleed_left_mm' => self::clampBleed($settings['bleed_left_mm'] ?? null, $defaults['bleed_left_mm']),
            'bleed_right_mm' => self::clampBleed($settings['bleed_right_mm'] ?? null, $defaults['bleed_right_mm']),
        ];
    }

    public static function clampBleed(mixed $value, float $default): float
    {
        if (! is_numeric($value)) {
            return $default;
        }

        return round(min(self::BLEED_MAX_MM, max(0, (float) $value)), 1);
    }

    public static function continuousLengthMinMm(float $widthMm): float
    {
        if ($widthMm > self::CONTINUOUS_NARROW_MAX_WIDTH_MM) {
            return self::CONTINUOUS_LENGTH_MIN_WIDE_MM;
        }

        return self::CONTINUOUS_LENGTH_MIN_NARROW_MM;
    }

    public static function clampContinuousLength(float $heightMm, float $widthMm): float
    {
        return round(
            min(self::CONTINUOUS_LENGTH_MAX_MM, max(self::continuousLengthMinMm($widthMm), $heightMm)),
            1
        );
    }

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
                'notes' => 'Default. Label length 12.7–300 mm.',
            ],
            'continuous_102' => [
                'label' => '102 mm continuous (wide)',
                'width_mm' => 102.0,
                'height_mm' => null,
                'continuous' => true,
                'printers' => ['QL-1100'],
                'notes' => 'QL-1100 only. Label length 25.4–300 mm.',
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
