<?php

namespace App\Models;

use App\Enums\RuPlacementFace;
use App\Enums\RuPlacementKind;
use Database\Factories\AssetRuPlacementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetRuPlacement extends Model
{
    /** @use HasFactory<AssetRuPlacementFactory> */
    use HasFactory;

    protected $fillable = [
        'rack_asset_id',
        'face',
        'start_ru',
        'ru_height',
        'kind',
        'child_asset_id',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'face' => RuPlacementFace::class,
            'kind' => RuPlacementKind::class,
            'start_ru' => 'integer',
            'ru_height' => 'integer',
        ];
    }

    public function rack(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'rack_asset_id');
    }

    public function childAsset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'child_asset_id');
    }
}
