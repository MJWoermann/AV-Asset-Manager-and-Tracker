<?php

namespace App\Models;

use Database\Factories\ManufacturerModelInterfaceTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ManufacturerModelInterfaceTemplate extends Model
{
    /** @use HasFactory<ManufacturerModelInterfaceTemplateFactory> */
    use HasFactory;

    protected $fillable = [
        'manufacturer_model_id',
        'name',
        'sort_order',
        'notes',
        'connection_type_id',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function manufacturerModel(): BelongsTo
    {
        return $this->belongsTo(ManufacturerModel::class);
    }

    public function connectionType(): BelongsTo
    {
        return $this->belongsTo(ConnectionType::class);
    }

    public function assetInterfaces(): HasMany
    {
        return $this->hasMany(AssetInterface::class);
    }
}
