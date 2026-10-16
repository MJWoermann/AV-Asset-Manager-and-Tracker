<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ManufacturerModelAlias extends Model
{
    protected $fillable = [
        'former_name',
        'manufacturer_id',
        'manufacturer_model_id',
    ];

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    public function manufacturerModel(): BelongsTo
    {
        return $this->belongsTo(ManufacturerModel::class);
    }

    public static function recordMerge(ManufacturerModel $from, ManufacturerModel $to): void
    {
        static::query()
            ->where('manufacturer_model_id', $from->id)
            ->update([
                'manufacturer_model_id' => $to->id,
                'manufacturer_id' => $to->manufacturer_id,
            ]);

        // Same model name under the replacement manufacturer needs no name alias;
        // manufacturer aliases cover imports that still use the old manufacturer.
        if ($from->name === $to->name) {
            return;
        }

        static::forgetMatching($from->name, $to->manufacturer_id);

        static::query()->create([
            'former_name' => $from->name,
            'manufacturer_id' => $to->manufacturer_id,
            'manufacturer_model_id' => $to->id,
        ]);
    }

    public static function forgetMatching(string $name, int $manufacturerId): void
    {
        $name = trim($name);

        static::query()
            ->where('manufacturer_id', $manufacturerId)
            ->where(function ($query) use ($name): void {
                $query->where('former_name', $name)
                    ->orWhereRaw('LOWER(former_name) = ?', [Str::lower($name)]);
            })
            ->delete();
    }

    public static function resolveModel(Manufacturer $manufacturer, string $name): ?ManufacturerModel
    {
        $name = trim($name);

        $alias = static::query()
            ->with('manufacturerModel')
            ->where('manufacturer_id', $manufacturer->id)
            ->where(function ($query) use ($name): void {
                $query->where('former_name', $name)
                    ->orWhereRaw('LOWER(former_name) = ?', [Str::lower($name)]);
            })
            ->first();

        return $alias?->manufacturerModel;
    }
}
