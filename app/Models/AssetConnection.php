<?php

namespace App\Models;

use Database\Factories\AssetConnectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetConnection extends Model
{
    /** @use HasFactory<AssetConnectionFactory> */
    use HasFactory;

    protected $fillable = [
        'interface_a_id',
        'interface_b_id',
        'connection_type_id',
        'protocol',
        'label',
        'notes',
    ];

    public function interfaceA(): BelongsTo
    {
        return $this->belongsTo(AssetInterface::class, 'interface_a_id');
    }

    public function interfaceB(): BelongsTo
    {
        return $this->belongsTo(AssetInterface::class, 'interface_b_id');
    }

    public function connectionType(): BelongsTo
    {
        return $this->belongsTo(ConnectionType::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(ConnectionType::class, 'connection_type_id');
    }
}
