<?php

namespace App\Models;

use App\Enums\CheckoutPurpose as SystemCheckoutPurpose;
use App\Enums\CheckoutPurposeBehavior;
use Database\Factories\CheckoutPurposeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CheckoutPurpose extends Model
{
    /** @use HasFactory<CheckoutPurposeFactory> */
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'behavior',
        'is_system',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'behavior' => CheckoutPurposeBehavior::class,
            'is_system' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<CheckoutPurpose>  $query
     * @return Builder<CheckoutPurpose>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function checkouts(): HasMany
    {
        return $this->hasMany(AssetCheckout::class, 'purpose', 'slug');
    }

    public function isRepair(): bool
    {
        return $this->behavior === CheckoutPurposeBehavior::Repair;
    }

    public function canDeactivate(): bool
    {
        return ! in_array($this->slug, [
            SystemCheckoutPurpose::Hire->value,
            SystemCheckoutPurpose::Repair->value,
        ], true);
    }

    public function canDelete(): bool
    {
        return ! $this->is_system;
    }
}
