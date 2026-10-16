<?php

namespace App\Models;

use App\Enums\LocationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class LocationAlias extends Model
{
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
        static::query()
            ->where('location_id', $from->id)
            ->update(['location_id' => $to->id]);

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
        $name = trim($name);
        $typeValue = $type instanceof LocationType ? $type->value : $type;

        static::query()
            ->where('type', $typeValue)
            ->where(function ($query) use ($name): void {
                $query->where('former_name', $name)
                    ->orWhereRaw('LOWER(former_name) = ?', [Str::lower($name)]);
            })
            ->delete();
    }

    public static function resolveLocation(string $name, LocationType $type): ?Location
    {
        $name = trim($name);

        $alias = static::query()
            ->with('location')
            ->where('type', $type->value)
            ->where(function ($query) use ($name): void {
                $query->where('former_name', $name)
                    ->orWhereRaw('LOWER(former_name) = ?', [Str::lower($name)]);
            })
            ->first();

        return $alias?->location;
    }
}
