<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomFieldDefinition extends Model
{
    /** @var list<string> */
    public const LAYOUT_TYPES = ['spacer', 'divider'];

    protected $fillable = [
        'custom_field_set_id',
        'name',
        'slug',
        'type',
        'options',
        'is_required',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_required' => 'boolean',
        ];
    }

    public function isLayoutElement(): bool
    {
        return in_array($this->type, self::LAYOUT_TYPES, true);
    }

    public function fieldSet(): BelongsTo
    {
        return $this->belongsTo(CustomFieldSet::class, 'custom_field_set_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(AssetCustomFieldValue::class);
    }
}
