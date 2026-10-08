<?php

namespace App\Models;

use App\Enums\AssetStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
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
        'device_sn',
        'ip_address',
        'mac_address',
        'test_tag_expiry',
        'quantity',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => AssetStatus::class,
            'purchase_date' => 'date',
            'warranty_expiry' => 'date',
            'test_tag_expiry' => 'date',
            'replacement_cost' => 'decimal:2',
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

    public function customFieldValues(): HasMany
    {
        return $this->hasMany(AssetCustomFieldValue::class);
    }

    public function isContainer(): bool
    {
        $slug = $this->itemType?->slug;

        return in_array($slug, ['rack-roadcase', 'rack', 'roadcase'], true);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(function (Builder $q) use ($term, $like) {
            $q->where('name', 'like', $like)
                ->orWhere('description', 'like', $like)
                ->orWhere('manufacturer', 'like', $like)
                ->orWhere('model', 'like', $like)
                ->orWhere('serial_number', 'like', $like)
                ->orWhere('fmi_ast', $term)
                ->orWhere('tp_barcode', $term)
                ->orWhere('rig_tag', $term)
                ->orWhere('device_sn', $term)
                ->orWhere('ip_address', $term)
                ->orWhere('mac_address', $term)
                ->orWhere('supplier', 'like', $like)
                ->orWhere('notes', 'like', $like)
                ->orWhereHas('customFieldValues', fn (Builder $cf) => $cf->where('value', 'like', $like));
        });
    }

    public function scopeFindByIdentifier(Builder $query, string $code): Builder
    {
        return $query->where(function (Builder $q) use ($code) {
            $q->where('fmi_ast', $code)
                ->orWhere('tp_barcode', $code)
                ->orWhere('rig_tag', $code)
                ->orWhere('device_sn', $code)
                ->orWhere('serial_number', $code)
                ->orWhere('mac_address', $code);
        });
    }
}
