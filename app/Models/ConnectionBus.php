<?php

namespace App\Models;

use Database\Factories\ConnectionBusFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ConnectionBus extends Model
{
    /** @use HasFactory<ConnectionBusFactory> */
    use HasFactory;

    protected $fillable = [
        'connection_type_id',
        'name',
        'notes',
    ];

    public function connectionType(): BelongsTo
    {
        return $this->belongsTo(ConnectionType::class);
    }

    public function interfaces(): BelongsToMany
    {
        return $this->belongsToMany(AssetInterface::class, 'connection_bus_members')
            ->withTimestamps();
    }
}
