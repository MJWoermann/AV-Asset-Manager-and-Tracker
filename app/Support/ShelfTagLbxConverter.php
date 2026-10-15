<?php

namespace App\Support;

class ShelfTagLbxConverter
{
    public const PT_TO_MM = 25.4 / 72;

    /**
     * @param  array{
     *     title: string|null,
     *     orientation: string,
     *     background: array{x: float, y: float, width: float, height: float},
     *     objects: list<array<string, mixed>>,
     *     files: array<string, string>
     * }  $parsed
     * @return array{
     *     name: string,
     *     paper_preset: string,
     *     width_mm: float,
     *     height_mm: float,
     *     orientation: string,
     *     length_mode: string,
     *     elements: list<array<string, mixed>>,
     *     logo_binary: string|null,
     *     logo_extension: string|null,
     *     warnings: list<string>
     * }
     */
    public function convert(array $parsed, ?string $fallbackName = null): array
    {
        $warnings = [];
        $landscape = ($parsed['orientation'] ?? 'portrait') === 'landscape';
        $background = $parsed['background'];

        $widthMm = $this->roundMm($this->ptToMm($landscape ? $background['height'] : $background['width']));
        $heightMm = $this->roundMm($this->ptToMm($landscape ? $background['width'] : $background['height']));

        $paperPreset = $this->resolvePaperPreset($widthMm, $heightMm);
        if (ShelfTagPaperPresets::isContinuous($paperPreset)) {
            $heightMm = ShelfTagPaperPresets::clampContinuousLength($heightMm);
        }

        $elements = [];
        $logoBinary = null;
        $logoExtension = null;
        $imageCount = 0;
        $z = 1;

        foreach ($parsed['objects'] as $index => $object) {
            $kind = (string) ($object['kind'] ?? '');
            $box = $this->transformBox(
                (float) ($object['x'] ?? 0),
                (float) ($object['y'] ?? 0),
                (float) ($object['width'] ?? 0),
                (float) ($object['height'] ?? 0),
                $background,
                $landscape
            );

            $mergeType = strtoupper((string) ($object['mergeType'] ?? 'NONE'));
            $objectName = (string) ($object['objectName'] ?? "object-{$index}");

            if ($kind === 'text') {
                if ($mergeType !== 'NONE' && $mergeType !== '') {
                    $elements[] = $this->makeElement('field', $box, $z++, [
                        'fieldKey' => 'name',
                        'fontSize' => $this->clampFontSize((float) ($object['fontSizePt'] ?? 11)),
                        'fontWeight' => ((int) ($object['fontWeight'] ?? 400)) >= 600 ? 'bold' : 'normal',
                        'align' => $this->mapAlign((string) ($object['align'] ?? 'LEFT')),
                        'color' => $this->normalizeColor((string) ($object['color'] ?? '#000000')),
                    ]);
                    $warnings[] = "Merged text \"{$objectName}\" imported as a field stub (name). Remap the field key in the designer.";

                    continue;
                }

                $elements[] = $this->makeElement('text', $box, $z++, [
                    'content' => (string) ($object['data'] ?? ''),
                    'fontSize' => $this->clampFontSize((float) ($object['fontSizePt'] ?? 11)),
                    'fontWeight' => ((int) ($object['fontWeight'] ?? 400)) >= 600 ? 'bold' : 'normal',
                    'align' => $this->mapAlign((string) ($object['align'] ?? 'LEFT')),
                    'color' => $this->normalizeColor((string) ($object['color'] ?? '#000000')),
                ]);

                continue;
            }

            if ($kind === 'barcode') {
                $protocol = strtoupper((string) ($object['protocol'] ?? ''));
                $data = (string) ($object['data'] ?? '');

                if ($protocol === 'QRCODE') {
                    $elements[] = $this->makeElement('qr', $box, $z++, [
                        'fieldKey' => '',
                        'content' => $data,
                        'ecc' => $this->mapQrEcc((string) ($object['eccLevel'] ?? '')),
                    ]);

                    continue;
                }

                if (in_array($protocol, ['CODE128', 'CODE39'], true)) {
                    $elements[] = $this->makeElement('barcode', $box, $z++, [
                        'fieldKey' => '',
                        'symbology' => strtolower($protocol) === 'code39' ? 'code39' : 'code128',
                        'showText' => (bool) ($object['humanReadable'] ?? true),
                    ]);
                    $sample = $data !== '' ? $data : '(empty)';
                    $warnings[] = "Barcode \"{$objectName}\" ({$protocol}) imported without a field key. Sample data: {$sample}. Bind a field in the designer.";

                    continue;
                }

                $warnings[] = "Skipped unsupported barcode protocol \"{$protocol}\" on \"{$objectName}\".";

                continue;
            }

            if ($kind === 'image') {
                $imageCount++;
                if ($imageCount > 1) {
                    $warnings[] = "Skipped extra image \"{$objectName}\" — layouts support one logo.";

                    continue;
                }

                $fileName = (string) ($object['fileName'] ?? '');
                $binary = $parsed['files'][$fileName] ?? null;
                if (! is_string($binary) || $binary === '') {
                    $warnings[] = "Image \"{$objectName}\" referenced missing file \"{$fileName}\".";

                    continue;
                }

                $logoBinary = $binary;
                $logoExtension = $this->guessExtension($fileName, (string) ($object['originalName'] ?? ''));
                $elements[] = $this->makeElement('logo', $box, $z++, []);

                continue;
            }

            if ($kind === 'draw') {
                $shape = strtolower((string) ($object['shape'] ?? ''));
                if ($shape === 'rectangle' || $shape === 'rect') {
                    $elements[] = $this->makeElement('rect', $box, $z++, [
                        'fill' => 'none',
                        'stroke' => '#000000',
                        'strokeWidth' => 0.3,
                    ]);

                    continue;
                }

                if ($shape === 'line') {
                    $elements[] = $this->makeElement('line', $box, $z++, [
                        'stroke' => '#000000',
                        'strokeWidth' => 0.3,
                    ]);

                    continue;
                }

                $warnings[] = "Skipped unsupported draw shape \"{$shape}\" on \"{$objectName}\".";

                continue;
            }

            $warnings[] = "Skipped unsupported object kind \"{$kind}\".";
        }

        $name = trim((string) ($parsed['title'] ?? ''));
        if ($name === '') {
            $name = trim((string) ($fallbackName ?? ''));
        }
        if ($name === '') {
            $name = 'Imported P-touch layout';
        }

        return [
            'name' => $name,
            'paper_preset' => $paperPreset,
            'width_mm' => $widthMm,
            'height_mm' => $heightMm,
            'orientation' => 'portrait',
            'length_mode' => ShelfTagPaperPresets::LENGTH_MODE_FIXED,
            'elements' => $elements,
            'logo_binary' => $logoBinary,
            'logo_extension' => $logoExtension,
            'warnings' => $warnings,
        ];
    }

    public function ptToMm(float $pt): float
    {
        return $pt * self::PT_TO_MM;
    }

    /**
     * @param  array{x: float, y: float, width: float, height: float}  $background
     * @return array{x: float, y: float, width: float, height: float}
     */
    public function transformBox(
        float $xPt,
        float $yPt,
        float $widthPt,
        float $heightPt,
        array $background,
        bool $landscape
    ): array {
        if ($landscape) {
            return [
                'x' => $this->roundMm($this->ptToMm($yPt - $background['y'])),
                'y' => $this->roundMm($this->ptToMm($xPt - $background['x'])),
                'width' => $this->roundMm($this->ptToMm($heightPt)),
                'height' => $this->roundMm($this->ptToMm($widthPt)),
            ];
        }

        return [
            'x' => $this->roundMm($this->ptToMm($xPt - $background['x'])),
            'y' => $this->roundMm($this->ptToMm($yPt - $background['y'])),
            'width' => $this->roundMm($this->ptToMm($widthPt)),
            'height' => $this->roundMm($this->ptToMm($heightPt)),
        ];
    }

    public function resolvePaperPreset(float $widthMm, float $heightMm): string
    {
        $bestDiecut = null;
        $bestDiecutDelta = PHP_FLOAT_MAX;

        foreach (ShelfTagPaperPresets::all() as $key => $preset) {
            if ($preset['continuous']) {
                continue;
            }

            $presetHeight = (float) $preset['height_mm'];
            $deltaW = abs($widthMm - (float) $preset['width_mm']);
            $deltaH = abs($heightMm - $presetHeight);
            if ($deltaW <= 2.0 && $deltaH <= 2.0) {
                $delta = $deltaW + $deltaH;
                if ($delta < $bestDiecutDelta) {
                    $bestDiecutDelta = $delta;
                    $bestDiecut = $key;
                }
            }
        }

        if ($bestDiecut !== null) {
            return $bestDiecut;
        }

        $bestContinuous = ShelfTagPaperPresets::DEFAULT;
        $bestDelta = PHP_FLOAT_MAX;

        foreach (ShelfTagPaperPresets::all() as $key => $preset) {
            if (! $preset['continuous']) {
                continue;
            }

            $delta = abs($widthMm - (float) $preset['width_mm']);
            if ($delta < $bestDelta) {
                $bestDelta = $delta;
                $bestContinuous = $key;
            }
        }

        return $bestContinuous;
    }

    /**
     * @param  array{x: float, y: float, width: float, height: float}  $box
     * @param  array<string, mixed>  $props
     * @return array<string, mixed>
     */
    protected function makeElement(string $type, array $box, int $z, array $props): array
    {
        return [
            'id' => 'el-'.substr(md5($type.'-'.$z.'-'.$box['x'].'-'.$box['y']), 0, 12),
            'type' => $type,
            'x' => $box['x'],
            'y' => $box['y'],
            'width' => max(0.5, $box['width']),
            'height' => max(0.5, $box['height']),
            'rotation' => 0,
            'z' => $z,
            'props' => $props,
        ];
    }

    protected function mapAlign(string $align): string
    {
        return match (strtoupper($align)) {
            'CENTER' => 'center',
            'RIGHT' => 'right',
            default => 'left',
        };
    }

    protected function mapQrEcc(string $eccLevel): string
    {
        $normalized = strtolower(trim($eccLevel));

        return match (true) {
            str_contains($normalized, '7') || $normalized === 'l' => 'L',
            str_contains($normalized, '25') || $normalized === 'q' => 'Q',
            str_contains($normalized, '30') || $normalized === 'h' => 'H',
            default => 'M',
        };
    }

    protected function clampFontSize(float $pt): float
    {
        return max(6.0, min(72.0, round($pt, 1)));
    }

    protected function normalizeColor(string $color): string
    {
        $color = trim($color);
        if (preg_match('/^#[0-9A-Fa-f]{6}$/', $color) === 1) {
            return strtoupper($color);
        }

        return '#000000';
    }

    protected function guessExtension(string $fileName, string $originalName): string
    {
        foreach ([$originalName, $fileName] as $name) {
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
                return $ext === 'jpeg' ? 'jpg' : $ext;
            }
        }

        return 'jpg';
    }

    protected function roundMm(float $mm): float
    {
        return round($mm, 1);
    }
}
