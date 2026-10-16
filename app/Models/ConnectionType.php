<?php

namespace App\Models;

use Database\Factories\ConnectionTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConnectionType extends Model
{
    /** @use HasFactory<ConnectionTypeFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'color',
        'icon',
        'allows_protocol',
        'allows_bus',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'allows_protocol' => 'boolean',
            'allows_bus' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function connections(): HasMany
    {
        return $this->hasMany(AssetConnection::class);
    }

    public function buses(): HasMany
    {
        return $this->hasMany(ConnectionBus::class);
    }

    public function interfaces(): HasMany
    {
        return $this->hasMany(AssetInterface::class);
    }
}
