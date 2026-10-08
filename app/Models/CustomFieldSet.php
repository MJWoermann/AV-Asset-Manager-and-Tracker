<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomFieldSet extends Model
{
    protected $fillable = ['name', 'slug', 'description'];

    public function definitions(): HasMany
    {
        return $this->hasMany(CustomFieldDefinition::class)->orderBy('sort_order');
    }

    public function itemTypes(): BelongsToMany
    {
        return $this->belongsToMany(ItemType::class, 'item_type_field_set');
    }
}
