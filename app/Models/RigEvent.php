<?php

namespace App\Models;

use App\Enums\RigEventStatus;
use App\Services\FileAttachmentService;
use Database\Factories\RigEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class RigEvent extends Model
{
    /** @use HasFactory<RigEventFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'venue_space',
        'event_date',
        'status',
        'engineer_required',
        'engineer_acquired',
        'engineer_notes',
        'structure_signed_name',
        'structure_signed_at',
        'structure_notes',
        'process_checklist',
        'variations_notes',
        'notes',
        'asset_list_id',
        'created_by',
    ];

    protected $attributes = [
        'status' => 'draft',
        'engineer_required' => false,
        'engineer_acquired' => false,
    ];

    protected function casts(): array
    {
        return [
            'status' => RigEventStatus::class,
            'event_date' => 'date',
            'engineer_required' => 'boolean',
            'engineer_acquired' => 'boolean',
            'structure_signed_at' => 'datetime',
            'process_checklist' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (RigEvent $event): void {
            app(FileAttachmentService::class)->detachAll($event);
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assetList(): BelongsTo
    {
        return $this->belongsTo(AssetList::class);
    }

    public function motorPatches(): HasMany
    {
        return $this->hasMany(RigEventMotorPatch::class)->orderBy('sort_order')->orderBy('id');
    }

    public function calculationLogs(): HasMany
    {
        return $this->hasMany(RiggingCalculationLog::class)->latest();
    }

    public function files(): MorphToMany
    {
        return $this->morphToMany(File::class, 'linkable', 'file_links')
            ->withPivot(['id', 'note'])
            ->withTimestamps()
            ->orderBy('files.title');
    }

    public function isSignedOff(): bool
    {
        return $this->structure_signed_at !== null && filled($this->structure_signed_name);
    }

    /**
     * Soft process checklist keys used by the Rig Event UI.
     *
     * @return list<string>
     */
    public static function checklistKeys(): array
    {
        return [
            'contractor_docs',
            'avpm_review',
            'head_rigger_escalation',
            'engineer_review',
            'approved_plan_returned',
            'security_ptw',
            'variations_noted',
            'structure_signoff',
            'return_to_security',
        ];
    }
}
