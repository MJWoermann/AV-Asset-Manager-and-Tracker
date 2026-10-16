<?php

namespace App\Models;

use App\Models\Concerns\BelongsToDepartment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class AssetList extends Model
{
    use BelongsToDepartment;

    protected $fillable = [
        'department_id',
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

    public function scopeForCompareSelection(Builder $query): Builder
    {
        return $query
            ->withCount('items')
            ->withMax('items', 'created_at')
            ->orderBy('type')
            ->orderBy('name');
    }

    /**
     * Label for compare-list select options.
     * Expects items_count (and preferably items_max_created_at) to be loaded.
     */
    public function compareOptionLabel(): string
    {
        $itemCount = (int) ($this->items_count ?? 0);
        $lastUpdated = $this->lastActivityAt();
        $updatedLabel = $lastUpdated
            ? $lastUpdated->timezone(config('app.timezone'))->format('Y-m-d H:i')
            : '—';

        return sprintf(
            '%s (%s) — %d %s, updated %s',
            $this->name,
            $this->type,
            $itemCount,
            Str::plural('item', $itemCount),
            $updatedLabel
        );
    }

    public function lastActivityAt(): ?Carbon
    {
        $listUpdatedAt = $this->updated_at;
        $lastItemAt = $this->items_max_created_at
            ? Carbon::parse($this->items_max_created_at)
            : null;

        if ($listUpdatedAt && $lastItemAt) {
            return $listUpdatedAt->greaterThan($lastItemAt) ? $listUpdatedAt : $lastItemAt;
        }

        return $listUpdatedAt ?? $lastItemAt;
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
