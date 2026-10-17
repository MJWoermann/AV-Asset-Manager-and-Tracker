<?php

namespace App\Models;

use App\Models\Concerns\MatchesFormerName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManufacturerModelAlias extends Model
{
    use MatchesFormerName;

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
        static::repointAliases('manufacturer_model_id', $from->id, [
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
        static::deleteMatchingFormerName(trim($name), [
            'manufacturer_id' => $manufacturerId,
        ]);
    }

    public static function resolveModel(Manufacturer $manufacturer, string $name): ?ManufacturerModel
    {
        $resolved = static::resolveRelationByFormerName(trim($name), 'manufacturerModel', [
            'manufacturer_id' => $manufacturer->id,
        ]);

        return $resolved instanceof ManufacturerModel ? $resolved : null;
    }
}
