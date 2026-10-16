<?php

namespace App\Models;

use App\Enums\SparePartMovementType;
use App\Services\FileAttachmentService;
use Database\Factories\SparePartMovementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class SparePartMovement extends Model
{
    /** @use HasFactory<SparePartMovementFactory> */
    use HasFactory;

    protected $fillable = [
        'spare_part_id',
        'type',
        'quantity_before',
        'quantity_after',
        'quantity_delta',
        'against_asset_id',
        'repair_id',
        'reason',
        'ticket_ref',
        'quoting_status',
        'user_id',
        'notes',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => SparePartMovementType::class,
            'quantity_before' => 'integer',
            'quantity_after' => 'integer',
            'quantity_delta' => 'integer',
            'recorded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (SparePartMovement $movement): void {
            app(FileAttachmentService::class)->detachAll($movement);
        });
    }

    public function sparePart(): BelongsTo
    {
        return $this->belongsTo(SparePart::class);
    }

    public function againstAsset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'against_asset_id');
    }

    public function repair(): BelongsTo
    {
        return $this->belongsTo(Repair::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function files(): MorphToMany
    {
        return $this->morphToMany(File::class, 'linkable', 'file_links')
            ->withPivot(['id', 'note'])
            ->withTimestamps()
            ->orderBy('files.title');
    }
}
