<?php

namespace App\Models;

use Database\Factories\RepairStepFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairStep extends Model
{
    /** @use HasFactory<RepairStepFactory> */
    use HasFactory;

    protected $fillable = [
        'repair_id',
        'sort_order',
        'title',
        'body',
        'is_required',
        'completed_at',
        'completed_by_user_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_required' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    public function repair(): BelongsTo
    {
        return $this->belongsTo(Repair::class);
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by_user_id');
    }

    public function isComplete(): bool
    {
        return $this->completed_at !== null;
    }
}
