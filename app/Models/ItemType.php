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
        'supports_rigging_calculator',
        'rigging_weight_kg',
    ];

    protected function casts(): array
    {
        return [
            'supports_ru_visualiser' => 'boolean',
            'supports_stock_tracking' => 'boolean',
            'supports_rigging_calculator' => 'boolean',
            'rigging_weight_kg' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (ItemType $itemType): void {
            ItemTypeAlias::forgetMatching($itemType->name, $itemType->slug);
        });

        static::updated(function (ItemType $itemType): void {
            if ($itemType->wasChanged(['name', 'slug'])) {
                ItemTypeAlias::forgetMatching($itemType->name, $itemType->slug);
            }
        });

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

    public function aliases(): HasMany
    {
        return $this->hasMany(ItemTypeAlias::class)->orderBy('former_name');
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
