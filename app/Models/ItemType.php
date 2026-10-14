<?php

namespace App\Models;

use App\Services\FileAttachmentService;
use Database\Factories\ItemTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class ItemType extends Model
{
    /** @use HasFactory<ItemTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'supports_ru_visualiser',
        'supports_stock_tracking',
    ];

    protected function casts(): array
    {
        return [
            'supports_ru_visualiser' => 'boolean',
            'supports_stock_tracking' => 'boolean',
        ];
    }

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

    public function interfaceTemplates(): HasMany
    {
        return $this->hasMany(ItemTypeInterfaceTemplate::class)->orderBy('sort_order')->orderBy('id');
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
