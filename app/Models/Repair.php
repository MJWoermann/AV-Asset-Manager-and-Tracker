<?php

namespace App\Models;

use App\Enums\RepairStatus;
use App\Services\FileAttachmentService;
use Database\Factories\RepairFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Repair extends Model
{
    /** @use HasFactory<RepairFactory> */
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'maintenance_log_id',
        'repair_procedure_id',
        'status',
        'assignee_user_id',
        'summary',
        'notes',
        'resolution_summary',
        'started_at',
        'completed_at',
        'completed_by_user_id',
        'asset_status_before',
        'asset_status_after',
    ];

    protected function casts(): array
    {
        return [
            'status' => RepairStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Repair $repair): void {
            app(FileAttachmentService::class)->detachAll($repair);
        });
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function maintenanceLog(): BelongsTo
    {
        return $this->belongsTo(AssetMaintenanceLog::class, 'maintenance_log_id');
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(RepairProcedure::class, 'repair_procedure_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_user_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by_user_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(RepairStep::class)->orderBy('sort_order')->orderBy('id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(SparePartMovement::class)->orderByDesc('recorded_at')->orderByDesc('id');
    }

    public function files(): MorphToMany
    {
        return $this->morphToMany(File::class, 'linkable', 'file_links')
            ->withPivot(['id', 'note'])
            ->withTimestamps()
            ->orderBy('files.title');
    }

    public function isActive(): bool
    {
        return $this->status instanceof RepairStatus
            ? $this->status->isActive()
            : in_array((string) $this->status, RepairStatus::activeValues(), true);
    }

    /**
     * @param  Builder<Repair>  $query
     * @return Builder<Repair>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', RepairStatus::activeValues());
    }
}
