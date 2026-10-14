<?php

namespace App\Models;

use App\Enums\FileCategory;
use App\Enums\FileSourceType;
use Database\Factories\FileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class File extends Model
{
    /** @use HasFactory<FileFactory> */
    use HasFactory;

    protected $fillable = [
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

    public function isUpload(): bool
    {
        return $this->source_type === FileSourceType::Upload;
    }

    public function isUrl(): bool
    {
        return $this->source_type === FileSourceType::Url;
    }
}
