<?php

namespace Database\Factories;

use App\Enums\CheckoutPurpose;
use App\Models\Asset;
use App\Models\AssetCheckout;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetCheckout>
 */
class AssetCheckoutFactory extends Factory
{
    protected $model = AssetCheckout::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'department_id' => null,
            'asset_id' => Asset::factory(),
            'checked_out_by_user_id' => User::factory(),
            'checked_out_to_user_id' => null,
            'checked_out_to_name' => fake()->name(),
            'checked_out_to_email' => fake()->safeEmail(),
            'checked_out_to_company' => fake()->company(),
            'location_id' => null,
            'expected_return_at' => null,
            'returned_at' => null,
            'returned_by_user_id' => null,
            'purpose' => CheckoutPurpose::Hire->value,
            'repair_id' => null,
            'asset_status_before' => 'available',
            'notes' => null,
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (): array => [
            'checked_out_to_user_id' => $user->id,
            'checked_out_to_name' => null,
            'checked_out_to_email' => null,
            'checked_out_to_company' => null,
        ]);
    }

    public function repairPurpose(?int $repairId = null): static
    {
        return $this->state(fn (): array => [
            'purpose' => CheckoutPurpose::Repair->value,
            'repair_id' => $repairId,
        ]);
    }

    public function returned(?User $by = null): static
    {
        return $this->state(fn (): array => [
            'returned_at' => now(),
            'returned_by_user_id' => $by?->id,
        ]);
    }
}
