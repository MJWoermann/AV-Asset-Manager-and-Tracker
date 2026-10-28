<?php

namespace App\Models;

use App\Enums\AssetStatus;
use App\Models\Concerns\BelongsToDepartment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScanSession extends Model
{
    use BelongsToDepartment;

    protected $fillable = [
        'department_id',
        'user_id',
        'event_list_id',
        'inventory_list_id',
        'location_id',
        'asset_status',
        'parent_asset_id',
        'replace_existing_parent',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'asset_status' => AssetStatus::class,
            'replace_existing_parent' => 'boolean',
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

    public function parentAsset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'parent_asset_id');
    }

    public function unknownScans(): HasMany
    {
        return $this->hasMany(UnknownScan::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
