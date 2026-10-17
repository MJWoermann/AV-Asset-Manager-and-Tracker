<?php

namespace App\Models;

use App\Enums\FileCategory;
use App\Enums\FileSourceType;
use App\Models\Concerns\BelongsToDepartment;
use App\Support\TextSearch;
use Database\Factories\FileFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class File extends Model
{
    /** @use HasFactory<FileFactory> */
    use BelongsToDepartment, HasFactory;

    protected $fillable = [
        'department_id',
        'title',
        'category',
        'source_type',
        'disk',
        'path',
        'content_hash',
        'mime_type',
        'size_bytes',
        'original_filename',
        'external_url',
        'thumbnail_path',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'category' => FileCategory::class,
            'source_type' => FileSourceType::class,
            'size_bytes' => 'integer',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function links(): HasMany
    {
        return $this->hasMany(FileLink::class);
    }

    public function assets(): MorphToMany
    {
        return $this->morphedByMany(Asset::class, 'linkable', 'file_links')
            ->withPivot(['id', 'note'])
            ->withTimestamps();
    }

    public function locations(): MorphToMany
    {
        return $this->morphedByMany(Location::class, 'linkable', 'file_links')
            ->withPivot(['id', 'note'])
            ->withTimestamps();
    }

    public function itemTypes(): MorphToMany
    {
        return $this->morphedByMany(ItemType::class, 'linkable', 'file_links')
            ->withPivot(['id', 'note'])
            ->withTimestamps();
    }

    public function stockMovements(): MorphToMany
    {
        return $this->morphedByMany(StockMovement::class, 'linkable', 'file_links')
            ->withPivot(['id', 'note'])
            ->withTimestamps();
    }

    public function maintenanceLogs(): MorphToMany
    {
        return $this->morphedByMany(AssetMaintenanceLog::class, 'linkable', 'file_links')
            ->withPivot(['id', 'note'])
            ->withTimestamps();
    }

    public function rigEvents(): MorphToMany
    {
        return $this->morphedByMany(RigEvent::class, 'linkable', 'file_links')
            ->withPivot(['id', 'note'])
            ->withTimestamps();
    }

    public function spareParts(): MorphToMany
    {
        return $this->morphedByMany(SparePart::class, 'linkable', 'file_links')
            ->withPivot(['id', 'note'])
            ->withTimestamps();
    }

    public function repairProcedures(): MorphToMany
    {
        return $this->morphedByMany(RepairProcedure::class, 'linkable', 'file_links')
            ->withPivot(['id', 'note'])
            ->withTimestamps();
    }

    public function repairs(): MorphToMany
    {
        return $this->morphedByMany(Repair::class, 'linkable', 'file_links')
            ->withPivot(['id', 'note'])
            ->withTimestamps();
    }

    public function isUpload(): bool
    {
        return $this->source_type === FileSourceType::Upload;
    }

    public function isUrl(): bool
    {
        return $this->source_type === FileSourceType::Url;
    }

    public function isPreviewableImage(): bool
    {
        if (! $this->isUpload()) {
            return false;
        }

        $mime = strtolower((string) $this->mime_type);

        return in_array($mime, ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'], true);
    }

    public function isPreviewablePdf(): bool
    {
        return $this->isUpload() && strtolower((string) $this->mime_type) === 'application/pdf';
    }

    public function isPreviewable(): bool
    {
        return $this->isPreviewableImage() || $this->isPreviewablePdf();
    }

    public function hasThumbnail(): bool
    {
        return filled($this->thumbnail_path);
    }

    /**
     * Multi-token search across title, filename, and external URL.
     *
     * @param  Builder<File>  $query
     * @return Builder<File>
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return TextSearch::constrainColumns($query, $term, [
            'title',
            'original_filename',
            'external_url',
        ]);
    }
}
