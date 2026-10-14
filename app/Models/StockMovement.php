<?php

namespace App\Models;

use App\Enums\StockMovementSource;
use App\Enums\StockMovementType;
use App\Services\FileAttachmentService;
use Database\Factories\StockMovementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class StockMovement extends Model
{
    /** @use HasFactory<StockMovementFactory> */
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'type',
        'quantity_before',
        'quantity_after',
        'quantity_delta',
        'user_id',
        'external_po_number',
        'external_po_url',
        'notes',
        'source',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => StockMovementType::class,
            'source' => StockMovementSource::class,
            'quantity_before' => 'integer',
            'quantity_after' => 'integer',
            'quantity_delta' => 'integer',
            'recorded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (StockMovement $movement): void {
            app(FileAttachmentService::class)->detachAll($movement);
        });
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
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
