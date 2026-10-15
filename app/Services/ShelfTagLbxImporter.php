<?php

namespace App\Services;

use App\Models\ShelfTagLayout;
use App\Models\User;
use App\Support\ShelfTagLbxConverter;
use App\Support\ShelfTagLbxParser;
use App\Support\ShelfTagPaperPresets;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ShelfTagLbxImporter
{
    public function __construct(
        protected ShelfTagLbxParser $parser,
        protected ShelfTagLbxConverter $converter,
    ) {}

    /**
     * @return array{layout: ShelfTagLayout, warnings: list<string>}
     */
    public function import(UploadedFile|string $file, ?User $user = null, ?string $fallbackName = null): array
    {
        $absolutePath = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        if (! is_string($absolutePath) || $absolutePath === '' || ! is_file($absolutePath)) {
            throw new InvalidArgumentException('Uploaded LBX file could not be read.');
        }

        if ($fallbackName === null && $file instanceof UploadedFile) {
            $fallbackName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        }

        $parsed = $this->parser->parse($absolutePath);
        $converted = $this->converter->convert($parsed, $fallbackName);

        $logoPath = null;
        if (is_string($converted['logo_binary']) && $converted['logo_binary'] !== '') {
            $extension = $converted['logo_extension'] ?: 'jpg';
            $logoPath = 'shelf-tag-logos/'.Str::uuid()->toString().'.'.$extension;
            Storage::disk('public')->put($logoPath, $converted['logo_binary']);
        }

        $layout = ShelfTagLayout::query()->create([
            'name' => $converted['name'],
            'paper_preset' => $converted['paper_preset'],
            'width_mm' => $converted['width_mm'],
            'height_mm' => $converted['height_mm'],
            'orientation' => $converted['orientation'],
            'length_mode' => $converted['length_mode'] ?? ShelfTagPaperPresets::LENGTH_MODE_FIXED,
            'elements' => $converted['elements'],
            'editor_settings' => ShelfTagPaperPresets::defaultEditorSettings(),
            'logo_path' => $logoPath,
            'created_by' => $user?->id,
        ]);

        return [
            'layout' => $layout,
            'warnings' => $converted['warnings'],
        ];
    }
}
