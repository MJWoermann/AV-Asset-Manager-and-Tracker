<?php

namespace App\Models;

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
        'elements',
        'logo_path',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'width_mm' => 'float',
            'height_mm' => 'float',
            'elements' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (ShelfTagLayout $layout): void {
            if ($layout->logo_path) {
                Storage::disk('public')->delete($layout->logo_path);
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logoUrl(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        return Storage::disk('public')->url($this->logo_path);
    }

    /**
     * Absolute filesystem path for DomPDF embedding.
     */
    public function logoAbsolutePath(): ?string
    {
        if (! $this->logo_path || ! Storage::disk('public')->exists($this->logo_path)) {
            return null;
        }

        return Storage::disk('public')->path($this->logo_path);
    }
}
