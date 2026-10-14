<?php

namespace Database\Factories;

use App\Enums\AssetStatus;
use App\Enums\StockMovementSource;
use App\Enums\StockMovementType;
use App\Models\Asset;
use App\Models\ItemType;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $before = fake()->numberBetween(0, 50);
        $after = fake()->numberBetween(0, 100);

        return [
            'asset_id' => fn (): int => Asset::factory()->create([
                'item_type_id' => ItemType::factory()->create([
                    'supports_stock_tracking' => true,
                ])->id,
                'status' => AssetStatus::Available,
                'quantity' => $after,
            ])->id,
            'type' => StockMovementType::Count,
            'quantity_before' => $before,
            'quantity_after' => $after,
            'quantity_delta' => $after - $before,
            'user_id' => null,
            'external_po_number' => null,
            'external_po_url' => null,
            'notes' => null,
            'source' => StockMovementSource::Web,
            'recorded_at' => now(),
        ];
    }

    public function restock(int $delta = 10): static
    {
        return $this->state(function (array $attributes) use ($delta): array {
            $before = (int) ($attributes['quantity_before'] ?? 0);
            $after = $before + $delta;

            return [
                'type' => StockMovementType::Restock,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'quantity_delta' => $delta,
            ];
        });
    }

    public function breakage(int $delta = 1): static
    {
        return $this->state(function (array $attributes) use ($delta): array {
            $before = max($delta, (int) ($attributes['quantity_before'] ?? $delta));
            $after = max(0, $before - $delta);

            return [
                'type' => StockMovementType::Breakage,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'quantity_delta' => $after - $before,
                'notes' => 'Damaged',
            ];
        });
    }
}
