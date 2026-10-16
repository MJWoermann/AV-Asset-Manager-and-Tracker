<?php

namespace App\Models;

use App\Services\FileAttachmentService;
use Database\Factories\RepairProcedureFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class RepairProcedure extends Model
{
    /** @use HasFactory<RepairProcedureFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'summary',
        'safety_notes',
        'tools_required',
        'estimated_minutes',
        'manufacturer_model_id',
        'item_type_id',
        'spare_part_id',
    ];

    protected function casts(): array
    {
        return [
            'estimated_minutes' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (RepairProcedure $procedure): void {
            app(FileAttachmentService::class)->detachAll($procedure);
        });
    }

    public function manufacturerModel(): BelongsTo
    {
        return $this->belongsTo(ManufacturerModel::class);
    }

    public function itemType(): BelongsTo
    {
        return $this->belongsTo(ItemType::class);
    }

    public function sparePart(): BelongsTo
    {
        return $this->belongsTo(SparePart::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(RepairProcedureStep::class)->orderBy('sort_order')->orderBy('id');
    }

    public function recommendedSpareParts(): BelongsToMany
    {
        return $this->belongsToMany(SparePart::class, 'repair_procedure_spare_part')
            ->withPivot(['id', 'quantity', 'sort_order'])
            ->withTimestamps()
            ->orderByPivot('sort_order')
            ->orderByPivot('id');
    }

    public function repairs(): HasMany
    {
        return $this->hasMany(Repair::class);
    }

    public function files(): MorphToMany
    {
        return $this->morphToMany(File::class, 'linkable', 'file_links')
            ->withPivot(['id', 'note'])
            ->withTimestamps()
            ->orderBy('files.title');
    }
}
