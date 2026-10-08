<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetListItem extends Model
{
    protected $fillable = [
        'asset_list_id',
        'asset_id',
        'quantity',
        'is_child_expand',
        'added_by',
    ];

    protected function casts(): array
    {
        return [
            'is_child_expand' => 'boolean',
        ];
    }

    public function list(): BelongsTo
    {
        return $this->belongsTo(AssetList::class, 'asset_list_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
