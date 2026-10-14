<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetList;
use App\Models\ShelfTagLayout;
use App\Support\ShelfTagFieldKeys;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Picqer\Barcode\BarcodeGeneratorPNG;

class ShelfTagRenderService
{
    public const MAX_BATCH = 200;

    /**
     * Resolve ordered assets for a list (all or selected subset).
     *
     * @param  list<int>|null  $assetIds
     * @return Collection<int, Asset>
     */
    public function resolveAssets(AssetList $list, ?array $assetIds = null): Collection
    {
        $query = $list->items()
            ->with([
                'asset.location',
                'asset.itemType',
                'asset.customFieldValues.definition',
            ])
            ->whereNotNull('asset_id')
            ->orderBy('id');

        if ($assetIds !== null) {
            $assetIds = array_values(array_unique(array_map('intval', $assetIds)));

            if ($assetIds === []) {
                throw ValidationException::withMessages([
                    'asset_ids' => 'Select at least one asset.',
                ]);
            }

            if (count($assetIds) > self::MAX_BATCH) {
                throw ValidationException::withMessages([
                    'asset_ids' => 'You can print at most '.self::MAX_BATCH.' shelf tags at a time.',
                ]);
            }

            $query->whereIn('asset_id', $assetIds);
        }

        $items = $query->get();

        if ($assetIds !== null) {
            $found = $items->pluck('asset_id')->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();
            $requested = collect($assetIds)->sort()->values()->all();

            if ($found !== $requested) {
                throw ValidationException::withMessages([
                    'asset_ids' => 'One or more selected assets are not on this list.',
                ]);
            }
        }

        $assets = $items
            ->map(fn ($item) => $item->asset)
            ->filter()
            ->unique('id')
            ->values();

        if ($assetIds === null && $assets->count() > self::MAX_BATCH) {
            throw ValidationException::withMessages([
                'asset_list_id' => 'This list has more than '.self::MAX_BATCH.' assets. Select a subset or split the list.',
            ]);
        }

        return $assets;
    }

    /**
     * Build printable label DTOs for the given assets and layout.
     *
     * @param  Collection<int, Asset>|iterable<int, Asset>  $assets
     * @return list<array{
     *     asset_id: int,
     *     width_mm: float,
     *     height_mm: float,
     *     elements: list<array<string, mixed>>
     * }>
     */
    public function buildLabels(ShelfTagLayout $layout, iterable $assets, bool $withServerImages = false): array
    {
        $labels = [];

        foreach ($assets as $asset) {
            $elements = [];

            foreach ($this->sortedElements($layout) as $element) {
                $elements[] = $this->hydrateElement($element, $asset, $layout, $withServerImages);
            }

            $labels[] = [
                'asset_id' => $asset->id,
                'width_mm' => (float) $layout->width_mm,
                'height_mm' => (float) $layout->height_mm,
                'elements' => $elements,
            ];
        }

        return $labels;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function sortedElements(ShelfTagLayout $layout): array
    {
        $elements = is_array($layout->elements) ? $layout->elements : [];

        usort($elements, function (array $a, array $b): int {
            return ((int) ($a['z'] ?? 0)) <=> ((int) ($b['z'] ?? 0));
        });

        return $elements;
    }

    /**
     * @param  array<string, mixed>  $element
     * @return array<string, mixed>
     */
    protected function hydrateElement(array $element, Asset $asset, ShelfTagLayout $layout, bool $withServerImages): array
    {
        $type = (string) ($element['type'] ?? 'text');
        $props = is_array($element['props'] ?? null) ? $element['props'] : [];
        $resolved = '';

        if (in_array($type, ['field', 'barcode', 'qr'], true)) {
            $fieldKey = (string) ($props['fieldKey'] ?? '');
            if ($fieldKey !== '') {
                $resolved = ShelfTagFieldKeys::resolve($asset, $fieldKey);
            } elseif ($type === 'qr' && filled($props['content'] ?? null)) {
                $resolved = (string) $props['content'];
            }
        }

        if ($type === 'text') {
            $resolved = (string) ($props['content'] ?? '');
        }

        $hydrated = [
            'id' => (string) ($element['id'] ?? ''),
            'type' => $type,
            'x' => (float) ($element['x'] ?? 0),
            'y' => (float) ($element['y'] ?? 0),
            'width' => (float) ($element['width'] ?? 10),
            'height' => (float) ($element['height'] ?? 10),
            'rotation' => (float) ($element['rotation'] ?? 0),
            'z' => (int) ($element['z'] ?? 0),
            'props' => $props,
            'value' => $resolved,
        ];

        if ($type === 'logo') {
            $hydrated['logo_url'] = $layout->logoUrl();
            if ($withServerImages) {
                $path = $layout->logoAbsolutePath();
                $hydrated['image_data_uri'] = $path ? $this->fileToDataUri($path) : null;
            }
        }

        if ($withServerImages && $resolved !== '') {
            if ($type === 'barcode') {
                $hydrated['image_data_uri'] = $this->barcodeDataUri(
                    $resolved,
                    (string) ($props['symbology'] ?? 'code128')
                );
            }

            if ($type === 'qr') {
                $hydrated['image_data_uri'] = $this->qrDataUri(
                    $resolved,
                    (string) ($props['ecc'] ?? 'M')
                );
            }
        }

        return $hydrated;
    }

    protected function barcodeDataUri(string $value, string $symbology): ?string
    {
        try {
            $generator = new BarcodeGeneratorPNG;
            $type = match (strtolower($symbology)) {
                'code39' => $generator::TYPE_CODE_39,
                default => $generator::TYPE_CODE_128,
            };
            $png = $generator->getBarcode($value, $type, 2, 60);

            return 'data:image/png;base64,'.base64_encode($png);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function qrDataUri(string $value, string $ecc): ?string
    {
        try {
            $level = match (strtoupper($ecc)) {
                'L' => ErrorCorrectionLevel::Low,
                'Q' => ErrorCorrectionLevel::Quartile,
                'H' => ErrorCorrectionLevel::High,
                default => ErrorCorrectionLevel::Medium,
            };

            $result = (new Builder(
                writer: new PngWriter,
                data: $value,
                errorCorrectionLevel: $level,
                size: 240,
                margin: 0,
            ))->build();

            return $result->getDataUri();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function fileToDataUri(string $path): ?string
    {
        if (! is_readable($path)) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/png';
        $data = file_get_contents($path);

        if ($data === false) {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode($data);
    }
}
