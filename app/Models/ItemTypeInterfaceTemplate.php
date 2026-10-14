<?php

namespace App\Models;

use Database\Factories\ItemTypeInterfaceTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemTypeInterfaceTemplate extends Model
{
    /** @use HasFactory<ItemTypeInterfaceTemplateFactory> */
    use HasFactory;

    protected $fillable = [
        'item_type_id',
        'name',
        'sort_order',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function itemType(): BelongsTo
    {
        return $this->belongsTo(ItemType::class);
    }

    public function assetInterfaces(): HasMany
    {
        return $this->hasMany(AssetInterface::class);
    }
}
