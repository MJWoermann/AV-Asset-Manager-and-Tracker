<?php

namespace App\Models;

use App\Enums\SparePartKind;
use App\Enums\SparePartLifecycleStatus;
use App\Enums\SparePartSeverity;
use App\Enums\SparePartStandbyMode;
use App\Enums\SparePartStockStatus;
use App\Models\Concerns\BelongsToDepartment;
use App\Services\FileAttachmentService;
use App\Support\TextSearch;
use Database\Factories\SparePartFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class SparePart extends Model
{
    /** @use HasFactory<SparePartFactory> */
    use BelongsToDepartment, HasFactory;

    protected $fillable = [
        'department_id',
        'code',
        'kind',
        'manufacturer_id',
        'manufacturer_model_id',
        'product_code',
        'component',
        'quantity_deployed',
        'quantity_on_hand',
        'min_holding',
        'max_holding',
        'location_id',
        'shelf_location',
        'stock_status',
        'standby_mode',
        'supplier',
        'unit_cost',
        'lead_time',
        'replacement_time',
        'lead_time_days',
        'replacement_time_minutes',
        'severity',
        'lifecycle_status',
        'superseded_by_id',
        'superseded_at',
        'ordered_at',
        'expected_arrival_at',
        'asset_id',
        'shelf_tag_layout_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'kind' => SparePartKind::class,
            'stock_status' => SparePartStockStatus::class,
            'standby_mode' => SparePartStandbyMode::class,
            'severity' => SparePartSeverity::class,
            'lifecycle_status' => SparePartLifecycleStatus::class,
            'quantity_deployed' => 'integer',
            'quantity_on_hand' => 'integer',
            'min_holding' => 'integer',
            'max_holding' => 'integer',
            'lead_time_days' => 'integer',
            'replacement_time_minutes' => 'integer',
            'unit_cost' => 'decimal:2',
            'superseded_at' => 'datetime',
            'ordered_at' => 'datetime',
            'expected_arrival_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (SparePart $part): void {
            app(FileAttachmentService::class)->detachAll($part);
        });
    }

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    public function manufacturerModel(): BelongsTo
    {
        return $this->belongsTo(ManufacturerModel::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function linkedAsset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    public function shelfTagLayout(): BelongsTo
    {
        return $this->belongsTo(ShelfTagLayout::class);
    }

    public function supersededBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'superseded_by_id');
    }

    public function supersedes(): HasMany
    {
        return $this->hasMany(self::class, 'superseded_by_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(SparePartMovement::class)->orderByDesc('recorded_at')->orderByDesc('id');
    }

    public function compatibleModels(): BelongsToMany
    {
        return $this->belongsToMany(ManufacturerModel::class, 'spare_part_models')
            ->withTimestamps();
    }

    public function compatibleAssets(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class, 'spare_part_assets')
            ->withTimestamps();
    }

    public function groupItems(): HasMany
    {
        return $this->hasMany(SpareGroupItem::class);
    }

    public function files(): MorphToMany
    {
        return $this->morphToMany(File::class, 'linkable', 'file_links')
            ->withPivot(['id', 'note'])
            ->withTimestamps()
            ->orderBy('files.title');
    }

    public function isBelowMin(): bool
    {
        if ($this->min_holding === null) {
            return false;
        }

        return $this->quantity_on_hand < $this->min_holding;
    }

    public function isNonActiveLifecycle(): bool
    {
        return $this->lifecycle_status !== SparePartLifecycleStatus::Active;
    }

    public function displayLabel(): string
    {
        return trim($this->code.' — '.$this->component);
    }

    public function scopeFindByCode(Builder $query, string $code): Builder
    {
        $normalized = preg_replace('/\s+/', '', trim($code)) ?? '';

        return $query->where('code', $normalized);
    }

    public function scopeActiveLifecycle(Builder $query): Builder
    {
        return $query->where('lifecycle_status', SparePartLifecycleStatus::Active);
    }

    /**
     * Multi-token search across spare codes and labels.
     *
     * @param  Builder<SparePart>  $query
     * @return Builder<SparePart>
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return TextSearch::constrain($query, $term, function (Builder $group, string $like): void {
            $group->orWhereLike('code', $like)
                ->orWhereLike('component', $like)
                ->orWhereLike('product_code', $like)
                ->orWhereLike('supplier', $like)
                ->orWhereLike('notes', $like)
                ->orWhereHas(
                    'manufacturer',
                    fn (Builder $related) => $related->whereLike('name', $like)
                )
                ->orWhereHas(
                    'manufacturerModel',
                    fn (Builder $related) => $related->whereLike('name', $like)
                );
        });
    }
}
