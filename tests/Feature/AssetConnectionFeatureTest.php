<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetConnection;
use App\Models\AssetInterface;
use App\Models\ConnectionType;
use App\Models\ItemType;
use App\Models\ItemTypeInterfaceTemplate;
use App\Models\Location;
use App\Models\User;
use App\Services\AssetConnectionService;
use Database\Seeders\ConnectionTypeSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetConnectionFeatureTest extends TestCase
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

    protected function manager(bool $feature = true): User
    {
        $user = User::factory()->create(['theme' => 'light']);
        $user->assignRole('inventory_manager');

        return $feature ? $this->withFeature($user) : $user;
    }

    protected function scanner(bool $feature = true): User
    {
        $user = User::factory()->create(['theme' => 'light']);
        $user->assignRole('scanner');

        return $feature ? $this->withFeature($user) : $user;
    }

    protected function makeItemType(?string $name = null): ItemType
    {
        $name ??= 'Type '.fake()->unique()->numerify('###');

        return ItemType::query()->create([
            'name' => $name,
            'slug' => str($name)->slug()->toString().'-'.fake()->unique()->numerify('###'),
        ]);
    }

    protected function makeAsset(?ItemType $itemType = null, ?Location $location = null, string $name = 'Asset'): Asset
    {
        $itemType ??= $this->makeItemType();

        return Asset::query()->create([
            'item_type_id' => $itemType->id,
            'location_id' => $location?->id,
            'name' => $name,
            'status' => AssetStatus::Available,
        ]);
    }

    public function test_flag_off_returns_404_for_connection_routes_and_hides_ui(): void
    {
        $user = $this->manager(feature: false);
        $asset = $this->makeAsset();

        $this->actingAs($user)
            ->get(route('reports.connection-map'))
            ->assertNotFound();

        $this->actingAs($user)
            ->get(route('connection-types.index'))
            ->assertNotFound();

        $this->actingAs($user)
            ->get(route('assets.show', [$asset, 'tab' => 'connections']))
            ->assertOk()
            ->assertDontSee('Connections</a>', false)
            ->assertDontSee('Add connection', false);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Connection map', false)
            ->assertDontSee('Connection types', false);
    }

    public function test_flag_on_exposes_nav_and_connections_tab(): void
    {
        $user = $this->manager();
        $asset = $this->makeAsset();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Connection map', false)
            ->assertSee('Connection types', false);

        $this->actingAs($user)
            ->get(route('assets.show', [$asset, 'tab' => 'connections']))
            ->assertOk()
            ->assertSee('Connections', false)
            ->assertSee('Add connection', false);
    }

    public function test_templates_copy_onto_asset_on_create(): void
    {
        $user = $this->manager();
        $itemType = $this->makeItemType('Mixer');

        ItemTypeInterfaceTemplate::query()->create([
            'item_type_id' => $itemType->id,
            'name' => 'LAN 1',
            'sort_order' => 10,
        ]);
        ItemTypeInterfaceTemplate::query()->create([
            'item_type_id' => $itemType->id,
            'name' => 'AES Out',
            'sort_order' => 20,
        ]);

        $response = $this->actingAs($user)->post(route('assets.store'), [
            'name' => 'Console A',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available->value,
            'quantity' => 1,
        ]);

        $asset = Asset::query()->where('name', 'Console A')->first();
        $this->assertNotNull($asset);
        $response->assertRedirect(route('assets.show', $asset));

        $this->assertEqualsCanonicalizing(
            ['LAN 1', 'AES Out'],
            $asset->interfaces()->pluck('name')->all()
        );
        $this->assertTrue($asset->interfaces()->where('name', 'LAN 1')->first()->isFromTemplate());
    }

    public function test_scanner_cannot_mutate_interfaces_or_connections(): void
    {
        $user = $this->scanner();
        $asset = $this->makeAsset();
        $peer = $this->makeAsset(name: 'Peer');
        $iface = AssetInterface::factory()->create(['asset_id' => $asset->id, 'name' => 'Port 1']);
        $peerIface = AssetInterface::factory()->create(['asset_id' => $peer->id, 'name' => 'In 1']);
        $network = ConnectionType::query()->where('slug', 'network')->firstOrFail();

        $this->actingAs($user)
            ->post(route('assets.interfaces.store', $asset), [
                'name' => 'HDMI Out',
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->put(route('assets.interfaces.update', [$asset, $iface]), [
                'name' => 'HDMI 1',
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->delete(route('assets.interfaces.destroy', [$asset, $iface]))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('assets.connections.store', $asset), [
                'local_interface_id' => $iface->id,
                'peer_interface_id' => $peerIface->id,
                'connection_type_id' => $network->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('asset_connections', 0);
    }

    public function test_adhoc_interface_crud(): void
    {
        $user = $this->manager();
        $asset = $this->makeAsset();

        $this->actingAs($user)
            ->post(route('assets.interfaces.store', $asset), [
                'name' => 'HDMI Out',
                'notes' => 'Front',
            ])
            ->assertRedirect(route('assets.show', [$asset, 'tab' => 'connections']));

        $interface = $asset->interfaces()->where('name', 'HDMI Out')->first();
        $this->assertNotNull($interface);
        $this->assertNull($interface->item_type_interface_template_id);

        $this->actingAs($user)
            ->put(route('assets.interfaces.update', [$asset, $interface]), [
                'name' => 'HDMI 1',
                'sort_order' => 5,
            ])
            ->assertRedirect(route('assets.show', [$asset, 'tab' => 'connections']));

        $this->assertSame('HDMI 1', $interface->fresh()->name);

        $this->actingAs($user)
            ->delete(route('assets.interfaces.destroy', [$asset, $interface]))
            ->assertRedirect(route('assets.show', [$asset, 'tab' => 'connections']));

        $this->assertDatabaseMissing('asset_interfaces', ['id' => $interface->id]);
    }

    public function test_connection_create_uses_canonical_ordering_and_allows_multi_link(): void
    {
        $user = $this->manager();
        $assetA = $this->makeAsset(name: 'Switch');
        $assetB = $this->makeAsset(name: 'Encoder');
        $ifaceA = AssetInterface::factory()->create(['asset_id' => $assetA->id, 'name' => 'Port 1']);
        $ifaceB = AssetInterface::factory()->create(['asset_id' => $assetB->id, 'name' => 'In 1']);
        $network = ConnectionType::query()->where('slug', 'network')->firstOrFail();
        $hdmi = ConnectionType::query()->where('slug', 'hdmi')->firstOrFail();

        $this->actingAs($user)
            ->post(route('assets.connections.store', $assetA), [
                'local_interface_id' => $ifaceA->id,
                'peer_interface_id' => $ifaceB->id,
                'connection_type_id' => $network->id,
                'label' => 'Primary',
            ])
            ->assertRedirect(route('assets.show', [$assetA, 'tab' => 'connections']));

        $first = AssetConnection::query()->first();
        $this->assertNotNull($first);
        $this->assertSame(min($ifaceA->id, $ifaceB->id), $first->interface_a_id);
        $this->assertSame(max($ifaceA->id, $ifaceB->id), $first->interface_b_id);

        $this->actingAs($user)
            ->post(route('assets.connections.store', $assetA), [
                'local_interface_id' => $ifaceA->id,
                'peer_interface_id' => $ifaceB->id,
                'connection_type_id' => $hdmi->id,
            ])
            ->assertRedirect(route('assets.show', [$assetA, 'tab' => 'connections']));

        $this->assertSame(2, AssetConnection::query()->count());
    }

    public function test_custom_protocol_is_required_when_type_allows_protocol(): void
    {
        $user = $this->manager();
        $assetA = $this->makeAsset(name: 'A');
        $assetB = $this->makeAsset(name: 'B');
        $ifaceA = AssetInterface::factory()->create(['asset_id' => $assetA->id]);
        $ifaceB = AssetInterface::factory()->create(['asset_id' => $assetB->id]);
        $custom = ConnectionType::query()->where('slug', 'custom-protocol')->firstOrFail();

        $this->actingAs($user)
            ->from(route('assets.show', [$assetA, 'tab' => 'connections']))
            ->post(route('assets.connections.store', $assetA), [
                'local_interface_id' => $ifaceA->id,
                'peer_interface_id' => $ifaceB->id,
                'connection_type_id' => $custom->id,
            ])
            ->assertSessionHasErrors('protocol');

        $this->actingAs($user)
            ->post(route('assets.connections.store', $assetA), [
                'local_interface_id' => $ifaceA->id,
                'peer_interface_id' => $ifaceB->id,
                'connection_type_id' => $custom->id,
                'protocol' => 'NDI|HX',
            ])
            ->assertRedirect(route('assets.show', [$assetA, 'tab' => 'connections']));

        $this->assertDatabaseHas('asset_connections', [
            'connection_type_id' => $custom->id,
            'protocol' => 'NDI|HX',
        ]);
    }

    public function test_connection_type_crud_is_gated_to_managers(): void
    {
        $scanner = $this->scanner();
        $manager = $this->manager();

        $this->actingAs($scanner)
            ->get(route('connection-types.index'))
            ->assertForbidden();

        $this->actingAs($manager)
            ->post(route('connection-types.store'), [
                'name' => 'Fiber',
                'color' => '#112233',
                'icon' => 'network',
                'allows_protocol' => '0',
                'sort_order' => 70,
            ])
            ->assertRedirect(route('connection-types.index'));

        $type = ConnectionType::query()->where('slug', 'fiber')->first();
        $this->assertNotNull($type);

        $this->actingAs($manager)
            ->put(route('connection-types.update', $type), [
                'name' => 'Fiber Optic',
                'color' => '#445566',
                'icon' => 'network',
                'allows_protocol' => '0',
                'sort_order' => 70,
            ])
            ->assertRedirect(route('connection-types.index'));

        $this->assertSame('Fiber Optic', $type->fresh()->name);
    }

    public function test_map_returns_only_filtered_connected_assets(): void
    {
        $user = $this->manager();
        $room = Location::query()->create([
            'name' => 'Control',
            'type' => 'room',
            'is_portable' => false,
        ]);
        $otherRoom = Location::query()->create([
            'name' => 'Stage',
            'type' => 'room',
            'is_portable' => false,
        ]);

        $type = $this->makeItemType();
        $assetA = $this->makeAsset($type, $room, 'Router');
        $assetB = $this->makeAsset($type, $room, 'Switch');
        $assetC = $this->makeAsset($type, $otherRoom, 'Spare');

        $ifaceA = AssetInterface::factory()->create(['asset_id' => $assetA->id]);
        $ifaceB = AssetInterface::factory()->create(['asset_id' => $assetB->id]);
        $ifaceC = AssetInterface::factory()->create(['asset_id' => $assetC->id]);
        $ifaceD = AssetInterface::factory()->create(['asset_id' => $assetA->id, 'name' => 'Spare port']);

        $network = ConnectionType::query()->where('slug', 'network')->firstOrFail();
        $hdmi = ConnectionType::query()->where('slug', 'hdmi')->firstOrFail();

        app(AssetConnectionService::class)->create($ifaceA, $ifaceB, $network);
        app(AssetConnectionService::class)->create($ifaceC, $ifaceD, $hdmi);

        $response = $this->actingAs($user)->get(route('reports.connection-map', [
            'location_id' => $room->id,
            'connection_type_ids' => [$network->id],
        ]));

        $response->assertOk();
        $response->assertSee('Router', false);
        $response->assertSee('Switch', false);
        $response->assertDontSee('Spare', false);
    }

    public function test_connections_tab_lists_peer_assets(): void
    {
        $user = $this->scanner();
        $assetA = $this->makeAsset(name: 'Display');
        $assetB = $this->makeAsset(name: 'Matrix');
        $ifaceA = AssetInterface::factory()->create(['asset_id' => $assetA->id, 'name' => 'HDMI In']);
        $ifaceB = AssetInterface::factory()->create(['asset_id' => $assetB->id, 'name' => 'Out 3']);
        $hdmi = ConnectionType::query()->where('slug', 'hdmi')->firstOrFail();

        app(AssetConnectionService::class)->create($ifaceA, $ifaceB, $hdmi, ['label' => 'Program']);

        $this->actingAs($user)
            ->get(route('assets.show', [$assetA, 'tab' => 'connections']))
            ->assertOk()
            ->assertSee('Matrix', false)
            ->assertSee('Out 3', false)
            ->assertSee('Program', false)
            ->assertSee(route('assets.show', [$assetB, 'tab' => 'connections']), false);
    }

    public function test_sync_missing_templates_for_existing_assets(): void
    {
        $user = $this->manager();
        $itemType = $this->makeItemType('Processor');
        $asset = $this->makeAsset($itemType, name: 'DSP-1');

        ItemTypeInterfaceTemplate::query()->create([
            'item_type_id' => $itemType->id,
            'name' => 'Dante Primary',
            'sort_order' => 10,
        ]);

        $this->assertSame(0, $asset->interfaces()->count());

        $this->actingAs($user)
            ->post(route('item-types.sync-interfaces', $itemType))
            ->assertRedirect(route('item-types.edit', $itemType));

        $this->assertSame(['Dante Primary'], $asset->fresh()->interfaces()->pluck('name')->all());
    }

    public function test_item_type_form_can_persist_interface_templates(): void
    {
        $user = $this->manager();

        $this->actingAs($user)
            ->post(route('item-types.store'), [
                'name' => 'Camera',
                'description' => null,
                'interface_templates' => [
                    ['name' => 'SDI Out', 'notes' => ''],
                    ['name' => 'LAN', 'notes' => 'PoE'],
                ],
            ])
            ->assertRedirect(route('item-types.index'));

        $itemType = ItemType::query()->where('name', 'Camera')->firstOrFail();
        $this->assertEqualsCanonicalizing(
            ['SDI Out', 'LAN'],
            $itemType->interfaceTemplates()->pluck('name')->all()
        );
    }
}
