<?php

namespace App\Models;

use App\Enums\AssetStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnknownScan extends Model
{
    protected $fillable = [
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

        // #region agent log
        $total = static::query()->count();
        $distinctCodes = static::query()->toBase()->distinct()->count('scanned_code');
        file_put_contents(base_path('debug-5c1253.log'), json_encode([
            'sessionId' => '5c1253',
            'hypothesisId' => 'C',
            'location' => 'UnknownScan.php:deleteDuplicatesKeepingLatest',
            'message' => 'dedupe_query_plan',
            'data' => [
                'total' => $total,
                'distinct_codes' => $distinctCodes,
                'keep_ids_count' => $keepIds->count(),
                'expected_deletes' => max(0, $total - $keepIds->count()),
            ],
            'timestamp' => (int) (microtime(true) * 1000),
            'runId' => 'post-fix',
        ])."\n", FILE_APPEND);
        // #endregion

        if ($keepIds->isEmpty()) {
            return 0;
        }

        return static::query()
            ->whereNotIn('id', $keepIds)
            ->delete();
    }
}
