<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetConnection;
use App\Models\AssetInterface;
use App\Models\ConnectionType;
use App\Services\AssetConnectionService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetConnection>
 */
class AssetConnectionFactory extends Factory
{
    protected $model = AssetConnection::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $assetA = Asset::factory()->create();
        $assetB = Asset::factory()->create();

        $interfaceA = AssetInterface::factory()->create(['asset_id' => $assetA->id]);
        $interfaceB = AssetInterface::factory()->create(['asset_id' => $assetB->id]);

        [$aId, $bId] = app(AssetConnectionService::class)
            ->canonicalizeInterfaceIds((int) $interfaceA->id, (int) $interfaceB->id);

        return [
            'interface_a_id' => $aId,
            'interface_b_id' => $bId,
            'connection_type_id' => ConnectionType::factory(),
            'protocol' => null,
            'label' => null,
            'notes' => null,
        ];
    }
}
