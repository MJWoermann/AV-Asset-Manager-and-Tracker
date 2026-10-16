<?php

namespace App\Models;

use App\Enums\MaintenanceLogType;
use App\Services\FileAttachmentService;
use Database\Factories\AssetMaintenanceLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class AssetMaintenanceLog extends Model
{
    /** @use HasFactory<AssetMaintenanceLogFactory> */
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'user_id',
        'type',
        'summary',
        'body',
        'occurred_at',
        'status_before',
        'status_after',
    ];

    protected function casts(): array
    {
        return [
            'type' => MaintenanceLogType::class,
            'occurred_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (AssetMaintenanceLog $log): void {
            app(FileAttachmentService::class)->detachAll($log);
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

    public function repairs(): HasMany
    {
        return $this->hasMany(Repair::class, 'maintenance_log_id');
    }

    public function files(): MorphToMany
    {
        return $this->morphToMany(File::class, 'linkable', 'file_links')
            ->withPivot(['id', 'note'])
            ->withTimestamps()
            ->orderBy('files.title');
    }
}
