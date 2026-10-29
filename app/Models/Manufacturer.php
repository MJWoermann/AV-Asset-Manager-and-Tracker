<?php

namespace App\Models;

use Database\Factories\ManufacturerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Manufacturer extends Model
{
    public const UNSPECIFIED_NAME = '(Unspecified)';

    /** @use HasFactory<ManufacturerFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function isUnspecified(): bool
    {
        return $this->name === self::UNSPECIFIED_NAME;
    }

    protected static function booted(): void
    {
        static::created(function (Manufacturer $manufacturer): void {
            ManufacturerAlias::forgetMatching($manufacturer->name);
        });

        static::updated(function (Manufacturer $manufacturer): void {
            if ($manufacturer->wasChanged('name')) {
                ManufacturerAlias::forgetMatching($manufacturer->name);
            }
        });
    }

    public function models(): HasMany
    {
        return $this->hasMany(ManufacturerModel::class)->orderBy('name');
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(ManufacturerAlias::class)->orderBy('former_name');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function assetsThroughModels(): HasManyThrough
    {
        return $this->hasManyThrough(Asset::class, ManufacturerModel::class);
    }
}
