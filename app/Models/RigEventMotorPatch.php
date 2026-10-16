<?php

namespace App\Models;

use Database\Factories\RigEventMotorPatchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RigEventMotorPatch extends Model
{
    /** @use HasFactory<RigEventMotorPatchFactory> */
    use HasFactory;

    protected $fillable = [
        'rig_event_id',
        'sort_order',
        'motor_label',
        'patch_to',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function rigEvent(): BelongsTo
    {
        return $this->belongsTo(RigEvent::class);
    }
}
