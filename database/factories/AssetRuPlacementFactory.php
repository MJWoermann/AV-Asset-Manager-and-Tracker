<?php

namespace Database\Factories;

use App\Enums\AssetStatus;
use App\Enums\RuPlacementFace;
use App\Enums\RuPlacementKind;
use App\Models\Asset;
use App\Models\AssetRuPlacement;
use App\Models\ItemType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetRuPlacement>
 */
class AssetRuPlacementFactory extends Factory
{
    protected $model = AssetRuPlacement::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'rack_asset_id' => fn (): int => $this->createRackAsset()->id,
            'face' => RuPlacementFace::Front,
            'start_ru' => 1,
            'ru_height' => 1,
            'full_depth' => false,
            'kind' => RuPlacementKind::Note,
            'child_asset_id' => null,
            'note' => fake()->sentence(),
        ];
    }

    public function forChildAsset(?Asset $child = null): static
    {
        return $this->state(function () use ($child): array {
            $child ??= Asset::create([
                'item_type_id' => ItemType::query()->firstOrCreate(
                    ['slug' => 'lx-fixture'],
                    ['name' => 'LX Fixture']
                )->id,
                'name' => fake()->words(2, true),
                'status' => AssetStatus::Available,
                'ru_height' => 1,
            ]);

            return [
                'kind' => RuPlacementKind::Asset,
                'child_asset_id' => $child->id,
                'note' => null,
                'ru_height' => max(1, (int) ($child->ru_height ?? 1)),
                'full_depth' => false,
            ];
        });
    }

    public function fullDepth(): static
    {
        return $this->state(fn (): array => [
            'full_depth' => true,
            'face' => RuPlacementFace::Front,
            'kind' => RuPlacementKind::Asset,
        ]);
    }

    public function rear(): static
    {
        return $this->state(fn (): array => [
            'face' => RuPlacementFace::Rear,
        ]);
    }

    protected function createRackAsset(): Asset
    {
        $type = ItemType::query()->firstOrCreate(
            ['slug' => 'rack-roadcase'],
            [
                'name' => 'Rack/RoadCase',
                'supports_ru_visualiser' => true,
            ]
        );

        if (! $type->supports_ru_visualiser) {
            $type->update(['supports_ru_visualiser' => true]);
        }

        return Asset::create([
            'item_type_id' => $type->id,
            'name' => fake()->words(2, true).' Rack',
            'status' => AssetStatus::Available,
            'ru_height' => 42,
            'ru_rear_enabled' => false,
        ]);
    }
}
