<?php

namespace App\Models;

use App\Enums\CheckoutPurpose as SystemCheckoutPurpose;
use App\Models\Concerns\BelongsToDepartment;
use BackedEnum;
use Database\Factories\AssetCheckoutFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetCheckout extends Model
{
    /** @use HasFactory<AssetCheckoutFactory> */
    use BelongsToDepartment, HasFactory;

    protected $fillable = [
        'department_id',
        'asset_id',
        'checked_out_by_user_id',
        'checked_out_to_user_id',
        'checked_out_to_name',
        'checked_out_to_email',
        'checked_out_to_company',
        'location_id',
        'expected_return_at',
        'returned_at',
        'returned_by_user_id',
        'purpose',
        'repair_id',
        'asset_status_before',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'expected_return_at' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }

    protected function purpose(): Attribute
    {
        return Attribute::make(
            set: function (BackedEnum|string|null $value): ?string {
                if ($value instanceof BackedEnum) {
                    return $value->value;
                }

                return $value;
            },
        );
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function checkedOutBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_out_by_user_id');
    }

    public function checkedOutTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_out_to_user_id');
    }

    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by_user_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function repair(): BelongsTo
    {
        return $this->belongsTo(Repair::class);
    }

    public function checkoutPurpose(): BelongsTo
    {
        return $this->belongsTo(CheckoutPurpose::class, 'purpose', 'slug');
    }

    public function purposeLabel(): string
    {
        $name = $this->checkoutPurpose?->name;

        if (is_string($name) && $name !== '') {
            return $name;
        }

        return SystemCheckoutPurpose::tryFrom((string) $this->purpose)?->label()
            ?? (string) $this->purpose;
    }

    public function isRepairPurpose(): bool
    {
        if ($this->checkoutPurpose !== null) {
            return $this->checkoutPurpose->isRepair();
        }

        return (string) $this->purpose === SystemCheckoutPurpose::Repair->value;
    }

    public function isOpen(): bool
    {
        return $this->returned_at === null;
    }

    /**
     * @param  Builder<AssetCheckout>  $query
     * @return Builder<AssetCheckout>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('returned_at');
    }

    public function assigneeLabel(): string
    {
        if ($this->checkedOutTo) {
            return $this->checkedOutTo->name;
        }

        $parts = array_filter([
            $this->checked_out_to_name,
            $this->checked_out_to_company,
            $this->checked_out_to_email,
        ]);

        return $parts !== [] ? implode(' · ', $parts) : '—';
    }
}
