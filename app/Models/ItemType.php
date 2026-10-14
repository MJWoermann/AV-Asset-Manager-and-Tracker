<?php

namespace App\Models;

use App\Services\FileAttachmentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class ItemType extends Model
{
    protected $fillable = ['name', 'slug', 'description'];

    protected static function booted(): void
    {
        static::deleting(function (ItemType $itemType): void {
            app(FileAttachmentService::class)->detachAll($itemType);
        });
    }

    public function fieldSets(): BelongsToMany
    {
        return $this->belongsToMany(CustomFieldSet::class, 'item_type_field_set');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function files(): MorphToMany
    {
        return $this->morphToMany(File::class, 'linkable', 'file_links')
            ->withPivot(['id', 'note'])
            ->withTimestamps()
            ->orderBy('files.title');
    }

    public function fieldDefinitions()
    {
        return CustomFieldDefinition::query()
            ->whereIn('custom_field_set_id', $this->fieldSets()->pluck('custom_field_sets.id'))
            ->orderBy('sort_order');
    }
}
