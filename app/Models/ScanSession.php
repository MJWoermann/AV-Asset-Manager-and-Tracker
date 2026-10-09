<?php

namespace App\Models;

use App\Enums\AssetStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScanSession extends Model
{
    protected $fillable = [
        'user_id',
        'event_list_id',
        'inventory_list_id',
        'location_id',
        'asset_status',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'asset_status' => AssetStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function eventList(): BelongsTo
    {
        return $this->belongsTo(AssetList::class, 'event_list_id');
    }

    public function inventoryList(): BelongsTo
    {
        return $this->belongsTo(AssetList::class, 'inventory_list_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
