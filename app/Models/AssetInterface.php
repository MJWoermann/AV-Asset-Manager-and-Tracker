<?php

namespace App\Models;

use Database\Factories\AssetInterfaceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetInterface extends Model
{
    /** @use HasFactory<AssetInterfaceFactory> */
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'name',
        'sort_order',
        'item_type_interface_template_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ItemTypeInterfaceTemplate::class, 'item_type_interface_template_id');
    }

    public function connectionsAsA(): HasMany
    {
        return $this->hasMany(AssetConnection::class, 'interface_a_id');
    }

    public function connectionsAsB(): HasMany
    {
        return $this->hasMany(AssetConnection::class, 'interface_b_id');
    }

    public function isFromTemplate(): bool
    {
        return $this->item_type_interface_template_id !== null;
    }
}
