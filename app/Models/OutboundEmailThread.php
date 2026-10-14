<?php

namespace App\Models;

use Database\Factories\OutboundEmailThreadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutboundEmailThread extends Model
{
    /** @use HasFactory<OutboundEmailThreadFactory> */
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'event_key',
        'provider_message_id',
        'conversation_id',
        'subject',
        'to_addresses',
        'sent_by',
    ];

    protected function casts(): array
    {
        return [
            'to_addresses' => 'array',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function inboundActions(): HasMany
    {
        return $this->hasMany(InboundEmailAction::class);
    }
}
