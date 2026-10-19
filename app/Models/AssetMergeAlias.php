<?php

namespace App\Models;

use App\Services\AssetDuplicateService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetMergeAlias extends Model
{
    protected $fillable = [
        'survivor_asset_id',
        'merged_asset_id',
        'department_id',
        'field',
        'value',
        'value_normalized',
    ];

    public function survivor(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'survivor_asset_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Record identifier aliases from an absorbed asset onto the survivor,
     * and re-point any aliases that previously targeted the absorbed asset.
     */
    public static function recordAbsorbedAsset(Asset $from, Asset $to): void
    {
        static::query()
            ->where('survivor_asset_id', $from->id)
            ->update([
                'survivor_asset_id' => $to->id,
                'department_id' => $to->department_id !== null ? (int) $to->department_id : null,
            ]);

        foreach (AssetDuplicateService::MATCH_KEYS as $field) {
            $raw = $from->getAttribute($field);
            if ($raw === null) {
                continue;
            }

            $value = trim((string) $raw);
            if ($value === '') {
                continue;
            }

            $normalized = app(AssetDuplicateService::class)->normalise($field, $value);
            if ($normalized === null) {
                continue;
            }

            static::query()->updateOrCreate(
                [
                    'field' => $field,
                    'value_normalized' => $normalized,
                ],
                [
                    'survivor_asset_id' => $to->id,
                    'merged_asset_id' => $from->id,
                    'department_id' => $to->department_id !== null ? (int) $to->department_id : null,
                    'value' => $value,
                ]
            );
        }
    }

    /**
     * Resolve a survivor asset id for an identifier field/value, if aliased.
     */
    public static function resolveSurvivorId(string $field, mixed $value): ?int
    {
        if (! in_array($field, AssetDuplicateService::MATCH_KEYS, true)) {
            return null;
        }

        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);
        if ($trimmed === '') {
            return null;
        }

        $normalized = app(AssetDuplicateService::class)->normalise($field, $trimmed);
        if ($normalized === null) {
            return null;
        }

        $survivorId = static::query()
            ->where('field', $field)
            ->where(function ($query) use ($trimmed, $normalized): void {
                $query->where('value', $trimmed)
                    ->orWhere('value_normalized', $normalized);
            })
            ->value('survivor_asset_id');

        return $survivorId !== null ? (int) $survivorId : null;
    }
}
