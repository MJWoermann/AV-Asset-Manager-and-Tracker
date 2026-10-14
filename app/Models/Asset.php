<?php

namespace App\Models;

use App\Enums\AssetStatus;
use App\Services\FileAttachmentService;
use App\Support\AssetSearch;
use Database\Factories\AssetFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Asset extends Model
{
    /** @use HasFactory<AssetFactory> */
    use HasFactory;

    protected $fillable = [
        'item_type_id',
        'location_id',
        'parent_id',
        'name',
        'description',
        'status',
        'manufacturer',
        'model',
        'serial_number',
        'purchase_date',
        'warranty_expiry',
        'supplier',
        'replacement_cost',
        'fmi_ast',
        'tp_barcode',
        'rig_tag',
        'ip_address',
        'mac_address',
        'test_tag_expiry',
        'quantity',
        'min_stock_level',
        'max_stock_level',
        'notes',
        'ru_height',
        'ru_rear_enabled',
    ];

    protected function casts(): array
    {
        return [
            'status' => AssetStatus::class,
            'purchase_date' => 'date',
            'warranty_expiry' => 'date',
            'test_tag_expiry' => 'date',
            'replacement_cost' => 'decimal:2',
            'quantity' => 'integer',
            'min_stock_level' => 'integer',
            'max_stock_level' => 'integer',
            'ru_height' => 'integer',
            'ru_rear_enabled' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Asset $asset) {
            if ($asset->parent_id && $asset->parent) {
                $asset->location_id = $asset->parent->location_id;
            }
        });

        static::updated(function (Asset $asset) {
            if ($asset->wasChanged('location_id')) {
                $asset->children()->each(function (Asset $child) use ($asset) {
                    $child->update(['location_id' => $asset->location_id]);
                });
            }
        });

        static::deleting(function (Asset $asset): void {
            app(FileAttachmentService::class)->detachAll($asset);
        });
    }

    public function itemType(): BelongsTo
    {
        return $this->belongsTo(ItemType::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function ruPlacements(): HasMany
    {
        return $this->hasMany(AssetRuPlacement::class, 'rack_asset_id');
    }

    public function ruPlacementAsChild(): HasMany
    {
        return $this->hasMany(AssetRuPlacement::class, 'child_asset_id');
    }

    public function customFieldValues(): HasMany
    {
        return $this->hasMany(AssetCustomFieldValue::class);
    }

    public function interfaces(): HasMany
    {
        return $this->hasMany(AssetInterface::class)->orderBy('sort_order')->orderBy('id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->orderBy('recorded_at')->orderBy('id');
    }

    public function stockTokens(): HasMany
    {
        return $this->hasMany(AssetStockToken::class);
    }

    public function maintenanceLogs(): HasMany
    {
        return $this->hasMany(AssetMaintenanceLog::class)
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable')->latest('created_at');
    }

    public function files(): MorphToMany
    {
        return $this->morphToMany(File::class, 'linkable', 'file_links')
            ->withPivot(['id', 'note'])
            ->withTimestamps()
            ->orderBy('files.title');
    }

    public function isContainer(): bool
    {
        $slug = $this->itemType?->slug;

        return in_array($slug, ['rack-roadcase', 'rack', 'roadcase'], true);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return AssetSearch::constrain($query, $term);
    }

    public function scopeOrderBySearchRelevance(Builder $query, ?string $term): Builder
    {
        return AssetSearch::orderByRelevance($query, $term);
    }

    public function scopeFindByIdentifier(Builder $query, string $code): Builder
    {
        return $query->where(function (Builder $q) use ($code) {
            $q->where('fmi_ast', $code)
                ->orWhere('tp_barcode', $code)
                ->orWhere('rig_tag', $code)
                ->orWhere('serial_number', $code)
                ->orWhere('mac_address', $code);
        });
    }
}
