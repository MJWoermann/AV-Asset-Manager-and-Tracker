<?php

namespace App\Models;

use Database\Factories\RiggingCalculationLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/**
 * Immutable calculation record for inputs/results.
 * Only rig_event_id may be updated (attach/detach).
 */
class RiggingCalculationLog extends Model
{
    /** @use HasFactory<RiggingCalculationLogFactory> */
    use HasFactory;

    protected $fillable = [
        'calculator',
        'title',
        'inputs',
        'results',
        'units',
        'rig_event_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'inputs' => 'array',
            'results' => 'array',
            'units' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function rigEvent(): BelongsTo
    {
        return $this->belongsTo(RigEvent::class);
    }

    public function update(array $attributes = [], array $options = []): bool
    {
        $keys = array_keys($attributes);

        if ($keys !== [] && $keys !== ['rig_event_id']) {
            throw new RuntimeException('Rigging calculation logs are immutable except for rig_event_id attach/detach.');
        }

        return parent::update($attributes, $options);
    }

    public function delete(): ?bool
    {
        throw new RuntimeException('Rigging calculation logs are immutable and cannot be deleted.');
    }

    /**
     * Attach or detach this log to a Rig Event without mutating calc payload.
     */
    public function syncRigEvent(?int $rigEventId): bool
    {
        return parent::update(['rig_event_id' => $rigEventId]);
    }
}
