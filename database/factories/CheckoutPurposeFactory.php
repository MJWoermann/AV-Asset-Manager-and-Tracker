<?php

namespace Database\Factories;

use App\Enums\CheckoutPurposeBehavior;
use App\Models\CheckoutPurpose;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CheckoutPurpose>
 */
class CheckoutPurposeFactory extends Factory
{
    protected $model = CheckoutPurpose::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'slug' => Str::limit(Str::slug($name), 32, ''),
            'name' => $name,
            'behavior' => CheckoutPurposeBehavior::OnEvent,
            'is_system' => false,
            'is_active' => true,
            'sort_order' => 10,
        ];
    }
}
