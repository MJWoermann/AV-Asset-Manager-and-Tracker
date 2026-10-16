<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ManufacturerAlias extends Model
{
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
        static::query()
            ->where('manufacturer_id', $from->id)
            ->update(['manufacturer_id' => $to->id]);

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
        $name = trim($name);

        static::query()
            ->where(function ($query) use ($name): void {
                $query->where('former_name', $name)
                    ->orWhereRaw('LOWER(former_name) = ?', [Str::lower($name)]);
            })
            ->delete();
    }

    public static function resolveManufacturer(string $name): ?Manufacturer
    {
        $name = trim($name);

        $alias = static::query()
            ->with('manufacturer')
            ->where(function ($query) use ($name): void {
                $query->where('former_name', $name)
                    ->orWhereRaw('LOWER(former_name) = ?', [Str::lower($name)]);
            })
            ->first();

        return $alias?->manufacturer;
    }
}
