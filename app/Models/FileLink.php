<?php

namespace App\Models;

use Database\Factories\FileLinkFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FileLink extends Model
{
    /** @use HasFactory<FileLinkFactory> */
    use HasFactory;

    protected $fillable = [
        'file_id',
        'linkable_type',
        'linkable_id',
        'note',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }

    public function typeLabel(): string
    {
        return match ($this->linkable_type) {
            Asset::class => 'Asset',
            Location::class => 'Location',
            ItemType::class => 'Item type',
            AssetMaintenanceLog::class => 'Maintenance log',
            StockMovement::class => 'Stock movement',
            default => class_basename((string) $this->linkable_type) ?: 'Unknown',
        };
    }

    public function displayName(): string
    {
        $linkable = $this->linkable;

        if ($linkable === null) {
            return 'Deleted (#'.$this->linkable_id.')';
        }

        if ($linkable instanceof AssetMaintenanceLog) {
            $summary = trim((string) ($linkable->summary ?: ''));

            return $summary !== '' ? $summary : 'Maintenance #'.$linkable->id;
        }

        if ($linkable instanceof StockMovement) {
            return 'Stock movement #'.$linkable->id;
        }

        $name = trim((string) ($linkable->name ?? ''));

        return $name !== '' ? $name : '#'.$linkable->id;
    }

    public function destinationUrl(): ?string
    {
        $linkable = $this->linkable;

        if ($linkable instanceof Asset) {
            return route('assets.show', $linkable);
        }

        if ($linkable instanceof Location) {
            return route('locations.edit', $linkable);
        }

        if ($linkable instanceof ItemType) {
            return route('item-types.edit', $linkable);
        }

        if ($linkable instanceof AssetMaintenanceLog && $linkable->asset_id) {
            return route('assets.show', [$linkable->asset_id, 'tab' => 'maintenance']);
        }

        if ($linkable instanceof StockMovement && $linkable->asset_id) {
            return route('assets.show', [$linkable->asset_id, 'tab' => 'stock']);
        }

        return null;
    }
}
