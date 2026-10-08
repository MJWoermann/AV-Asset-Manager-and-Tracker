<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetList extends Model
{
    protected $fillable = [
        'name',
        'type',
        'description',
        'created_by',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(AssetListItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isInventory(): bool
    {
        return $this->type === 'inventory';
    }

    public function isEvent(): bool
    {
        return $this->type === 'event';
    }
}
