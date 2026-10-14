<?php

namespace App\Models;

use App\Enums\InboundEmailActionStatus;
use Database\Factories\InboundEmailActionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InboundEmailAction extends Model
{
    /** @use HasFactory<InboundEmailActionFactory> */
    use HasFactory;

    protected $fillable = [
        'status',
        'source_message_id',
        'from_address',
        'subject',
        'snippet',
        'raw_headers',
        'inbound_email_rule_id',
        'outbound_email_thread_id',
        'asset_id',
        'asset_maintenance_log_id',
        'proposed_payload',
        'processed_by',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => InboundEmailActionStatus::class,
            'raw_headers' => 'array',
            'proposed_payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(InboundEmailRule::class, 'inbound_email_rule_id');
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(OutboundEmailThread::class, 'outbound_email_thread_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function maintenanceLog(): BelongsTo
    {
        return $this->belongsTo(AssetMaintenanceLog::class, 'asset_maintenance_log_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
