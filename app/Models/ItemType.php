<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemType extends Model
{
    protected $fillable = ['name', 'slug', 'description'];

    public function fieldSets(): BelongsToMany
    {
        return $this->belongsToMany(CustomFieldSet::class, 'item_type_field_set');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function fieldDefinitions()
    {
        return CustomFieldDefinition::query()
            ->whereIn('custom_field_set_id', $this->fieldSets()->pluck('custom_field_sets.id'))
            ->orderBy('sort_order');
    }
}
