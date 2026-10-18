<?php

namespace App\Models;

use App\Enums\LocationType;
use App\Models\Concerns\MatchesFormerName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationAlias extends Model
{
    use MatchesFormerName;

    protected $fillable = [
        'former_name',
        'type',
        'location_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => LocationType::class,
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Point the removed location's name (and aliases already pointing at it) at the survivor.
     */
    public static function recordMerge(Location $from, Location $to): void
    {
        static::repointAliases('location_id', $from->id, [
            'location_id' => $to->id,
        ]);

        $sameIdentity = $from->name === $to->name && $from->type === $to->type;

        if ($sameIdentity) {
            return;
        }

        static::forgetMatching($from->name, $from->type);

        static::query()->create([
            'former_name' => $from->name,
            'type' => $from->type,
            'location_id' => $to->id,
        ]);
    }

    public static function forgetMatching(string $name, LocationType|string $type): void
    {
        $typeValue = $type instanceof LocationType ? $type->value : $type;

        static::deleteMatchingFormerName(trim($name), ['type' => $typeValue]);
    }

    public static function resolveLocation(string $name, LocationType $type): ?Location
    {
        $resolved = static::resolveRelationByFormerName(trim($name), 'location', [
            'type' => $type->value,
        ]);

        return $resolved instanceof Location ? $resolved : null;
    }
}
