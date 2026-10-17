<?php

namespace App\Models;

use App\Enums\AssetStatus;
use App\Models\Concerns\BelongsToDepartment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class UnknownScan extends Model
{
    use BelongsToDepartment;

    protected $fillable = [
        'department_id',
        'scanned_code',
        'user_id',
        'scan_session_id',
        'event_list_id',
        'inventory_list_id',
        'location_id',
        'quantity',
        'asset_status',
        'note',
        'resolved_asset_id',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'asset_status' => AssetStatus::class,
            'resolved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scanSession(): BelongsTo
    {
        return $this->belongsTo(ScanSession::class);
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

    public function resolvedAsset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'resolved_asset_id');
    }

    public function isResolved(): bool
    {
        return $this->resolved_asset_id !== null;
    }

    public function markResolved(Asset $asset): void
    {
        $this->update([
            'resolved_asset_id' => $asset->id,
            'resolved_at' => now(),
        ]);
    }

    /**
     * Resolve this unknown scan against an existing asset.
     *
     * Optionally sets tp_barcode from the scanned code when empty and unique,
     * and adds the asset to the related event list without duplicating.
     */
    public function linkToExisting(Asset $asset, bool $addToEventList = false, bool $setIdentifier = false, ?int $userId = null): void
    {
        if ($this->isResolved()) {
            throw ValidationException::withMessages([
                'asset_id' => 'This unknown scan is already resolved.',
            ]);
        }

        if ($setIdentifier && blank($asset->tp_barcode)) {
            $code = $this->scanned_code;
            $collision = Asset::query()
                ->where('tp_barcode', $code)
                ->where('id', '!=', $asset->id)
                ->exists();

            if (! $collision) {
                $asset->update(['tp_barcode' => $code]);
            }
        }

        $this->markResolved($asset);

        if ($addToEventList && $this->event_list_id) {
            AssetListItem::query()->firstOrCreate(
                [
                    'asset_list_id' => $this->event_list_id,
                    'asset_id' => $asset->id,
                ],
                [
                    'quantity' => max(1, (int) $this->quantity),
                    'is_child_expand' => (bool) $asset->parent_id,
                    'added_by' => $userId,
                ]
            );
        }
    }

    /**
     * @param  Builder<UnknownScan>  $query
     * @return Builder<UnknownScan>
     */
    public function scopeUnresolved(Builder $query): Builder
    {
        return $query->whereNull('resolved_asset_id');
    }

    /**
     * Delete every unknown scan row.
     */
    public static function clearAll(): int
    {
        return static::query()->delete();
    }

    /**
     * Delete duplicate scanned codes, keeping the latest row (highest id) for each code.
     */
    public static function deleteDuplicatesKeepingLatest(): int
    {
        $keepIds = static::query()
            ->toBase()
            ->selectRaw('MAX(id) as id')
            ->groupBy('scanned_code')
            ->pluck('id');

        if ($keepIds->isEmpty()) {
            return 0;
        }

        return static::query()
            ->whereNotIn('id', $keepIds)
            ->delete();
    }
}
