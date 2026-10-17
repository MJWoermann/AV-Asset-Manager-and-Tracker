<?php

namespace App\Models;

use App\Models\Concerns\MatchesFormerName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManufacturerAlias extends Model
{
    use MatchesFormerName;

    protected $fillable = [
        'former_name',
        'manufacturer_id',
    ];

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    public static function recordMerge(Manufacturer $from, Manufacturer $to): void
    {
        static::repointAliases('manufacturer_id', $from->id, [
            'manufacturer_id' => $to->id,
        ]);

        if ($from->name === $to->name) {
            return;
        }

        static::forgetMatching($from->name);

        static::query()->create([
            'former_name' => $from->name,
            'manufacturer_id' => $to->id,
        ]);
    }

    public static function forgetMatching(string $name): void
    {
        static::deleteMatchingFormerName(trim($name));
    }

    public static function resolveManufacturer(string $name): ?Manufacturer
    {
        $resolved = static::resolveRelationByFormerName(trim($name), 'manufacturer');

        return $resolved instanceof Manufacturer ? $resolved : null;
    }
}
