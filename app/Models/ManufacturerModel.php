<?php

namespace App\Models;

use Database\Factories\ManufacturerModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ManufacturerModel extends Model
{
    /** @use HasFactory<ManufacturerModelFactory> */
    use HasFactory;

    protected $fillable = [
        'manufacturer_id',
        'name',
    ];

    protected static function booted(): void
    {
        static::created(function (ManufacturerModel $model): void {
            ManufacturerModelAlias::forgetMatching($model->name, (int) $model->manufacturer_id);
        });

        static::updated(function (ManufacturerModel $model): void {
            if ($model->wasChanged(['name', 'manufacturer_id'])) {
                ManufacturerModelAlias::forgetMatching($model->name, (int) $model->manufacturer_id);
            }
        });
    }

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(ManufacturerModelAlias::class)->orderBy('former_name');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function interfaceTemplates(): HasMany
    {
        return $this->hasMany(ManufacturerModelInterfaceTemplate::class)->orderBy('sort_order')->orderBy('id');
    }
}
