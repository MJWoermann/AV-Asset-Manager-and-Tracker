<?php

namespace App\Models;

use App\Enums\InboundEmailRuleAction;
use App\Enums\MaintenanceLogType;
use Database\Factories\InboundEmailRuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InboundEmailRule extends Model
{
    /** @use HasFactory<InboundEmailRuleFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'is_active',
        'priority',
        'match_conditions',
        'action',
        'log_type',
        'field_mapping',
        'fault_status',
        'require_review',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'require_review' => 'boolean',
            'priority' => 'integer',
            'match_conditions' => 'array',
            'field_mapping' => 'array',
            'action' => InboundEmailRuleAction::class,
            'log_type' => MaintenanceLogType::class,
        ];
    }

    public function actions(): HasMany
    {
        return $this->hasMany(InboundEmailAction::class);
    }
}
