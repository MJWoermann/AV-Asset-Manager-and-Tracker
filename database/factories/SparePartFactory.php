<?php

namespace Database\Factories;

use App\Enums\SparePartKind;
use App\Enums\SparePartLifecycleStatus;
use App\Enums\SparePartSeverity;
use App\Enums\SparePartStandbyMode;
use App\Enums\SparePartStockStatus;
use App\Models\SparePart;
use App\Support\SparePartLuhn;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SparePart>
 */
class SparePartFactory extends Factory
{
    protected $model = SparePart::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => SparePartLuhn::generate(7),
            'kind' => SparePartKind::SparePart,
            'manufacturer_id' => null,
            'manufacturer_model_id' => null,
            'product_code' => fake()->optional()->bothify('SKU-####'),
            'component' => fake()->words(2, true),
            'quantity_deployed' => fake()->numberBetween(0, 20),
            'quantity_on_hand' => fake()->numberBetween(0, 10),
            'min_holding' => 1,
            'max_holding' => 5,
            'location_id' => null,
            'shelf_location' => fake()->optional()->bothify('Shelf ?#'),
            'stock_status' => SparePartStockStatus::InStock,
            'standby_mode' => SparePartStandbyMode::None,
            'supplier' => fake()->optional()->company(),
            'unit_cost' => fake()->optional()->randomFloat(2, 10, 5000),
            'lead_time' => '2 weeks',
            'replacement_time' => '30 min',
            'lead_time_days' => 14,
            'replacement_time_minutes' => 30,
            'severity' => SparePartSeverity::Moderate,
            'lifecycle_status' => SparePartLifecycleStatus::Active,
            'superseded_by_id' => null,
            'superseded_at' => null,
            'ordered_at' => null,
            'expected_arrival_at' => null,
            'asset_id' => null,
            'shelf_tag_layout_id' => null,
            'notes' => null,
        ];
    }

    public function replacementAsset(): static
    {
        return $this->state(fn (): array => [
            'kind' => SparePartKind::ReplacementAsset,
        ]);
    }

    public function superseded(SparePart $successor): static
    {
        return $this->state(fn (): array => [
            'lifecycle_status' => SparePartLifecycleStatus::Superseded,
            'superseded_by_id' => $successor->id,
            'superseded_at' => now(),
        ]);
    }
}
