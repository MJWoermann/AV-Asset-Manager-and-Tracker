<?php

namespace Database\Factories;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetStockToken;
use App\Models\ItemType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AssetStockToken>
 */
class AssetStockTokenFactory extends Factory
{
    protected $model = AssetStockToken::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $plain = Str::random(48);

        return [
            'asset_id' => fn (): int => Asset::factory()->create([
                'item_type_id' => ItemType::factory()->create([
                    'supports_stock_tracking' => true,
                ])->id,
                'status' => AssetStatus::Available,
            ])->id,
            'token_hash' => hash('sha256', $plain),
            'label' => 'kanban-a',
            'last_used_at' => null,
            'revoked_at' => null,
        ];
    }

    public function revoked(): static
    {
        return $this->state(fn (): array => [
            'revoked_at' => now(),
        ]);
    }
}
