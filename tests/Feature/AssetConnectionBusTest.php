<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetInterface;
use App\Models\ConnectionBus;
use App\Models\ConnectionType;
use App\Models\ItemType;
use App\Models\User;
use App\Services\AssetConnectionService;
use App\Services\ConnectionBusService;
use Database\Seeders\ConnectionTypeSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetConnectionBusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(ConnectionTypeSeeder::class);
    }

    protected function withFeature(User $user): User
    {
        $user->preferences = [
            'features' => [
                'asset_connections' => true,
            ],
        ];
        $user->save();

        return $user->fresh();
    }

    protected function manager(): User
    {
        $user = User::factory()->create(['theme' => 'light']);
        $user->assignRole('inventory_manager');

        return $this->withFeature($user);
    }

    protected function scanner(): User
    {
        $user = User::factory()->create(['theme' => 'light']);
        $user->assignRole('scanner');

        return $this->withFeature($user);
    }

    protected function makeItemType(?string $name = null): ItemType
    {
        $name ??= 'Type '.fake()->unique()->numerify('###');

        return ItemType::query()->create([
            'name' => $name,
            'slug' => str($name)->slug()->toString().'-'.fake()->unique()->numerify('###'),
        ]);
    }

    protected function makeAsset(?ItemType $itemType = null, string $name = 'Asset'): Asset
    {
        $itemType ??= $this->makeItemType();

        return Asset::query()->create([
            'item_type_id' => $itemType->id,
            'name' => $name,
            'status' => AssetStatus::Available,
        ]);
    }

    public function test_dante_and_dmx_seed_with_allows_bus(): void
    {
        $dante = ConnectionType::query()->where('slug', 'dante-network')->firstOrFail();
        $dmx = ConnectionType::query()->where('slug', 'dmx')->firstOrFail();
        $hdmi = ConnectionType::query()->where('slug', 'hdmi')->firstOrFail();

        $this->assertTrue($dante->allows_bus);
        $this->assertTrue($dmx->allows_bus);
        $this->assertFalse($hdmi->allows_bus);
    }

    public function test_connection_type_can_enable_allows_bus(): void
    {
        $user = $this->manager();
        $type = ConnectionType::query()->where('slug', 'network')->firstOrFail();

        $this->actingAs($user)
            ->put(route('connection-types.update', $type), [
                'name' => $type->name,
                'color' => $type->color,
                'icon' => $type->icon,
                'allows_protocol' => '0',
                'allows_bus' => '1',
                'sort_order' => $type->sort_order,
            ])
            ->assertRedirect(route('connection-types.index'));

        $this->assertTrue($type->fresh()->allows_bus);
    }

    public function test_scanner_cannot_join_device_bus(): void
    {
        $user = $this->scanner();
        $asset = $this->makeAsset(name: 'Console');
        $dante = ConnectionType::query()->where('slug', 'dante-network')->firstOrFail();
        $iface = AssetInterface::factory()->create([
            'asset_id' => $asset->id,
            'name' => 'Dante Primary',
            'connection_type_id' => $dante->id,
        ]);

        $this->actingAs($user)
            ->post(route('assets.buses.store', $asset), [
                'local_interface_id' => $iface->id,
                'new_bus_name' => 'FOH Dante',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('connection_buses', 0);
    }

    public function test_join_creates_bus_and_membership_for_bus_capable_type(): void
    {
        $user = $this->manager();
        $assetA = $this->makeAsset(name: 'Console');
        $assetB = $this->makeAsset(name: 'Amp');
        $dante = ConnectionType::query()->where('slug', 'dante-network')->firstOrFail();

        $ifaceA = AssetInterface::factory()->create([
            'asset_id' => $assetA->id,
            'name' => 'Dante Primary',
            'connection_type_id' => $dante->id,
        ]);
        $ifaceB = AssetInterface::factory()->create([
            'asset_id' => $assetB->id,
            'name' => 'Dante Primary',
            'connection_type_id' => $dante->id,
        ]);

        $this->actingAs($user)
            ->post(route('assets.buses.store', $assetA), [
                'local_interface_id' => $ifaceA->id,
                'new_bus_name' => 'FOH Dante',
            ])
            ->assertRedirect(route('assets.show', [$assetA, 'tab' => 'connections']));

        $bus = ConnectionBus::query()->where('name', 'FOH Dante')->first();
        $this->assertNotNull($bus);
        $this->assertSame($dante->id, $bus->connection_type_id);
        $this->assertTrue($bus->interfaces()->whereKey($ifaceA->id)->exists());

        $this->actingAs($user)
            ->post(route('assets.buses.store', $assetB), [
                'local_interface_id' => $ifaceB->id,
                'connection_bus_id' => $bus->id,
            ])
            ->assertRedirect(route('assets.show', [$assetB, 'tab' => 'connections']));

        $this->assertSame(2, $bus->fresh()->interfaces()->count());

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Asset::class,
            'auditable_id' => $assetA->id,
            'action' => 'bus_joined',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Asset::class,
            'auditable_id' => $assetB->id,
            'action' => 'bus_joined',
        ]);
    }

    public function test_cannot_join_bus_when_type_disallows_bus(): void
    {
        $user = $this->manager();
        $asset = $this->makeAsset(name: 'Display');
        $hdmi = ConnectionType::query()->where('slug', 'hdmi')->firstOrFail();
        $iface = AssetInterface::factory()->create([
            'asset_id' => $asset->id,
            'name' => 'HDMI Out',
            'connection_type_id' => $hdmi->id,
        ]);

        $this->actingAs($user)
            ->from(route('assets.show', [$asset, 'tab' => 'connections']))
            ->post(route('assets.buses.store', $asset), [
                'local_interface_id' => $iface->id,
                'new_bus_name' => 'HDMI Bus',
            ])
            ->assertSessionHasErrors('local_interface_id');

        $this->assertDatabaseCount('connection_buses', 0);
    }

    public function test_interface_can_only_join_one_bus(): void
    {
        $user = $this->manager();
        $asset = $this->makeAsset(name: 'Console');
        $dante = ConnectionType::query()->where('slug', 'dante-network')->firstOrFail();
        $iface = AssetInterface::factory()->create([
            'asset_id' => $asset->id,
            'connection_type_id' => $dante->id,
        ]);

        $this->actingAs($user)
            ->post(route('assets.buses.store', $asset), [
                'local_interface_id' => $iface->id,
                'new_bus_name' => 'Bus A',
            ])
            ->assertRedirect();

        $this->actingAs($user)
            ->from(route('assets.show', [$asset, 'tab' => 'connections']))
            ->post(route('assets.buses.store', $asset), [
                'local_interface_id' => $iface->id,
                'new_bus_name' => 'Bus B',
            ])
            ->assertSessionHasErrors('local_interface_id');

        $this->assertSame(1, ConnectionBus::query()->count());
    }

    public function test_leave_bus_removes_membership(): void
    {
        $user = $this->manager();
        $asset = $this->makeAsset(name: 'Console');
        $dante = ConnectionType::query()->where('slug', 'dante-network')->firstOrFail();
        $iface = AssetInterface::factory()->create([
            'asset_id' => $asset->id,
            'connection_type_id' => $dante->id,
        ]);

        $bus = app(ConnectionBusService::class)->join($iface, ['new_bus_name' => 'Stage Dante']);

        $this->actingAs($user)
            ->delete(route('assets.interfaces.bus.destroy', [$asset, $iface]))
            ->assertRedirect(route('assets.show', [$asset, 'tab' => 'connections']));

        $this->assertFalse($bus->fresh()->interfaces()->whereKey($iface->id)->exists());
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Asset::class,
            'auditable_id' => $asset->id,
            'action' => 'bus_left',
        ]);
    }

    public function test_connections_tab_shows_device_bus_section(): void
    {
        $user = $this->manager();
        $asset = $this->makeAsset(name: 'Console');
        $dante = ConnectionType::query()->where('slug', 'dante-network')->firstOrFail();
        AssetInterface::factory()->create([
            'asset_id' => $asset->id,
            'name' => 'Dante Primary',
            'connection_type_id' => $dante->id,
        ]);

        $this->actingAs($user)
            ->get(route('assets.show', [$asset, 'tab' => 'connections']))
            ->assertOk()
            ->assertSee('Device buses', false)
            ->assertSee('Join device bus', false);
    }

    public function test_build_graph_includes_bus_hub_and_member_edges(): void
    {
        $assetA = $this->makeAsset(name: 'Console');
        $assetB = $this->makeAsset(name: 'Amp');
        $assetC = $this->makeAsset(name: 'Speaker');
        $dante = ConnectionType::query()->where('slug', 'dante-network')->firstOrFail();

        $ifaceA = AssetInterface::factory()->create([
            'asset_id' => $assetA->id,
            'connection_type_id' => $dante->id,
        ]);
        $ifaceB = AssetInterface::factory()->create([
            'asset_id' => $assetB->id,
            'connection_type_id' => $dante->id,
        ]);
        $ifaceC = AssetInterface::factory()->create([
            'asset_id' => $assetC->id,
            'connection_type_id' => $dante->id,
        ]);

        $buses = app(ConnectionBusService::class);
        $bus = $buses->join($ifaceA, ['new_bus_name' => 'FOH Dante']);
        $buses->join($ifaceB, ['connection_bus_id' => $bus->id]);
        $buses->join($ifaceC, ['connection_bus_id' => $bus->id]);

        $graph = app(AssetConnectionService::class)->buildGraph(['asset_id' => $assetA->id]);

        $this->assertSame(3, $graph['asset_count']);
        $busNode = collect($graph['nodes'])->first(
            fn (array $node): bool => ($node['data']['kind'] ?? null) === 'bus'
        );
        $this->assertNotNull($busNode);
        $this->assertSame('FOH Dante', $busNode['data']['name']);
        $this->assertSame(3, $busNode['data']['member_count']);
        $this->assertCount(3, $graph['edges']);
        $this->assertTrue(collect($graph['edges'])->every(
            fn (array $edge): bool => ($edge['data']['is_bus'] ?? false) === true
        ));
    }

    public function test_changing_interface_type_leaves_mismatched_bus(): void
    {
        $user = $this->manager();
        $asset = $this->makeAsset(name: 'Console');
        $dante = ConnectionType::query()->where('slug', 'dante-network')->firstOrFail();
        $hdmi = ConnectionType::query()->where('slug', 'hdmi')->firstOrFail();
        $iface = AssetInterface::factory()->create([
            'asset_id' => $asset->id,
            'name' => 'Port',
            'connection_type_id' => $dante->id,
        ]);

        $bus = app(ConnectionBusService::class)->join($iface, ['new_bus_name' => 'FOH Dante']);

        $this->actingAs($user)
            ->put(route('assets.interfaces.update', [$asset, $iface]), [
                'name' => 'Port',
                'connection_type_id' => $hdmi->id,
            ])
            ->assertRedirect(route('assets.show', [$asset, 'tab' => 'connections']));

        $this->assertFalse($bus->fresh()->interfaces()->whereKey($iface->id)->exists());
        $this->assertSame($hdmi->id, $iface->fresh()->connection_type_id);
    }
}
