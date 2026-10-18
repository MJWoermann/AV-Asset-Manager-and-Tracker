<?php

namespace App\Services;

use App\Models\ShelfTagLayout;
use App\Models\SparePart;
use App\Support\BarcodeDataUri;
use App\Support\ShelfTagFieldKeys;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class SpareTagRenderService
{
    public const MAX_BATCH = 200;

    /**
     * @param  list<int>|null  $spareIds
     * @return Collection<int, SparePart>
     */
    public function resolveSpares(?array $spareIds = null): Collection
    {
        $query = SparePart::query()
            ->with(['manufacturer', 'manufacturerModel', 'location'])
            ->orderBy('code');

        if ($spareIds !== null) {
            $spareIds = array_values(array_unique(array_map('intval', $spareIds)));

            if ($spareIds === []) {
                throw ValidationException::withMessages([
                    'spare_ids' => 'Select at least one spare.',
                ]);
            }

            if (count($spareIds) > self::MAX_BATCH) {
                throw ValidationException::withMessages([
                    'spare_ids' => 'You can print at most '.self::MAX_BATCH.' labels at a time.',
                ]);
            }

            $query->whereIn('id', $spareIds);
        }

        $parts = $query->get();

        if ($spareIds !== null && $parts->count() !== count($spareIds)) {
            throw ValidationException::withMessages([
                'spare_ids' => 'One or more selected spares could not be found.',
            ]);
        }

        if ($spareIds === null && $parts->count() > self::MAX_BATCH) {
            throw ValidationException::withMessages([
                'spare' => 'Too many spares to print at once. Filter or select a subset.',
            ]);
        }

        return $parts;
    }

    /**
     * @param  Collection<int, SparePart>|iterable<int, SparePart>  $spares
     * @return list<array<string, mixed>>
     */
    public function buildLabels(ShelfTagLayout $layout, iterable $spares, bool $withServerImages = false): array
    {
        $labels = [];

        foreach ($spares as $spare) {
            $elements = [];

            foreach ($this->sortedElements($layout) as $element) {
                $elements[] = $this->hydrateElement($element, $spare, $layout, $withServerImages);
            }

            $labels[] = [
                'spare_id' => $spare->id,
                'width_mm' => $layout->displayWidthMm(),
                'height_mm' => $layout->displayHeightMm(),
                'orientation' => $layout->isLandscape() ? 'landscape' : 'portrait',
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
    protected function hydrateElement(array $element, SparePart $spare, ShelfTagLayout $layout, bool $withServerImages): array
    {
        $type = (string) ($element['type'] ?? 'text');
        $props = is_array($element['props'] ?? null) ? $element['props'] : [];
        $resolved = '';

        if (in_array($type, ['field', 'barcode', 'qr'], true)) {
            $fieldKey = (string) ($props['fieldKey'] ?? '');
            if ($fieldKey !== '') {
                $inner = ShelfTagFieldKeys::spareKeyFromPrefixed($fieldKey) ?? $fieldKey;
                $resolved = ShelfTagFieldKeys::resolveSpare($spare, $inner);
            } elseif ($type === 'qr' && filled($props['content'] ?? null)) {
                $resolved = (string) $props['content'];
            }
        }

        if ($type === 'text') {
            $resolved = (string) ($props['content'] ?? '');
        }

        if ($type === 'qr' && ($props['useSpareUrl'] ?? false)) {
            $resolved = route('spares.show', $spare);
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
        return BarcodeDataUri::png($value, $symbology);
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

            $result = Builder::create()
                ->writer(new PngWriter)
                ->data($value)
                ->errorCorrectionLevel($level)
                ->size(300)
                ->margin(10)
                ->build();

            return $result->getDataUri();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function fileToDataUri(string $absolutePath): ?string
    {
        if (! is_readable($absolutePath)) {
            return null;
        }

        $mime = mime_content_type($absolutePath) ?: 'application/octet-stream';

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($absolutePath));
    }
}
