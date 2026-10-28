<?php

namespace App\Models;

use App\Support\ShelfTagPaperPresets;
use Database\Factories\ShelfTagLayoutFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ShelfTagLayout extends Model
{
    /** @use HasFactory<ShelfTagLayoutFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'paper_preset',
        'width_mm',
        'height_mm',
        'orientation',
        'length_mode',
        'elements',
        'editor_settings',
        'logo_path',
        'logo_file_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'width_mm' => 'float',
            'height_mm' => 'float',
            'elements' => 'array',
            'editor_settings' => 'array',
        ];
    }

    /** @var list<string> */
    public const LOGO_MIME_TYPES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
    ];

    protected static function booted(): void
    {
        static::deleting(function (ShelfTagLayout $layout): void {
            $layout->releasePublicLogo();
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logoFile(): BelongsTo
    {
        return $this->belongsTo(File::class, 'logo_file_id');
    }

    public static function isUsableLogoFile(?File $file): bool
    {
        if ($file === null || ! $file->isUpload() || ! $file->path || ! $file->disk) {
            return false;
        }

        return in_array(strtolower((string) $file->mime_type), self::LOGO_MIME_TYPES, true);
    }

    public function logoUrl(): ?string
    {
        $file = $this->logoFile;

        if ($this->logo_file_id && self::isUsableLogoFile($file)) {
            return route('labels.layouts.logo-file', $file);
        }

        if (! $this->logo_path) {
            return null;
        }

        return asset('storage/'.ltrim((string) $this->logo_path, '/'));
    }

    /**
     * Delete a legacy public-disk logo when no other layout still uses that path.
     */
    public function releasePublicLogo(): void
    {
        if (! $this->logo_path) {
            return;
        }

        $path = $this->logo_path;
        $shared = static::query()
            ->where('logo_path', $path)
            ->when($this->exists, fn ($query) => $query->whereKeyNot($this->getKey()))
            ->exists();

        if (! $shared) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Absolute filesystem path for DomPDF embedding.
     */
    public function logoAbsolutePath(): ?string
    {
        $file = $this->logoFile;

        if ($this->logo_file_id && self::isUsableLogoFile($file)) {
            $driver = config('filesystems.disks.'.$file->disk.'.driver');
            $disk = Storage::disk($file->disk);

            if ($driver === 'local' && $disk->exists($file->path)) {
                return $disk->path($file->path);
            }
        }

        if (! $this->logo_path || ! Storage::disk('public')->exists($this->logo_path)) {
            return null;
        }

        return Storage::disk('public')->path($this->logo_path);
    }

    /**
     * @return array{show_grid: bool, snap_to_grid: bool, grid_size_mm: float}
     */
    public function normalizedEditorSettings(): array
    {
        return ShelfTagPaperPresets::normalizeEditorSettings(
            is_array($this->editor_settings) ? $this->editor_settings : null
        );
    }

    public function isAutoLength(): bool
    {
        return $this->length_mode === ShelfTagPaperPresets::LENGTH_MODE_AUTO;
    }

    public function isLandscape(): bool
    {
        return $this->orientation === 'landscape';
    }

    /**
     * Print/preview width in mm (length along feed when landscape).
     */
    public function displayWidthMm(): float
    {
        return $this->isLandscape() ? (float) $this->height_mm : (float) $this->width_mm;
    }

    /**
     * Print/preview height in mm (tape width when landscape).
     */
    public function displayHeightMm(): float
    {
        return $this->isLandscape() ? (float) $this->width_mm : (float) $this->height_mm;
    }
}
