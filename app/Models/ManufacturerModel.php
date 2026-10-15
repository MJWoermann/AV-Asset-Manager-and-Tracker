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

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}
