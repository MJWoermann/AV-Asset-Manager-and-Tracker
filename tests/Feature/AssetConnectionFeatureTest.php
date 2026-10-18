<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Enums\LocationType;
use App\Models\Asset;
use App\Models\AssetConnection;
use App\Models\AssetInterface;
use App\Models\ConnectionType;
use App\Models\ItemType;
use App\Models\ItemTypeInterfaceTemplate;
use App\Models\Location;
use App\Models\Manufacturer;
use App\Models\ManufacturerModel;
use App\Models\ManufacturerModelInterfaceTemplate;
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
        $network = ConnectionType::query()->where('slug', 'network')->firstOrFail();
        $iface = AssetInterface::factory()->create([
            'asset_id' => $asset->id,
            'name' => 'Port 1',
            'connection_type_id' => $network->id,
        ]);
        $peerIface = AssetInterface::factory()->create([
            'asset_id' => $peer->id,
            'name' => 'In 1',
            'connection_type_id' => $network->id,
        ]);

        $this->actingAs($user)
            ->post(route('assets.interfaces.store', $asset), [
                'name' => 'HDMI Out',
                'connection_type_id' => $network->id,
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->put(route('assets.interfaces.update', [$asset, $iface]), [
                'name' => 'HDMI 1',
                'connection_type_id' => $network->id,
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->delete(route('assets.interfaces.destroy', [$asset, $iface]))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('assets.connections.store', $asset), [
                'local_interface_id' => $iface->id,
                'peer_asset_id' => $peer->id,
                'peer_interface_id' => $peerIface->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('asset_connections', 0);
    }

    public function test_adhoc_interface_crud(): void
    {
        $user = $this->manager();
        $asset = $this->makeAsset();
        $hdmi = ConnectionType::query()->where('slug', 'hdmi')->firstOrFail();
        $network = ConnectionType::query()->where('slug', 'network')->firstOrFail();

        $this->actingAs($user)
            ->post(route('assets.interfaces.store', $asset), [
                'name' => 'HDMI Out',
                'connection_type_id' => $hdmi->id,
                'notes' => 'Front',
            ])
            ->assertRedirect(route('assets.show', [$asset, 'tab' => 'connections']));

        $interface = $asset->interfaces()->where('name', 'HDMI Out')->first();
        $this->assertNotNull($interface);
        $this->assertNull($interface->item_type_interface_template_id);
        $this->assertSame($hdmi->id, $interface->connection_type_id);

        $this->actingAs($user)
            ->put(route('assets.interfaces.update', [$asset, $interface]), [
                'name' => 'HDMI 1',
                'connection_type_id' => $network->id,
                'sort_order' => 5,
            ])
            ->assertRedirect(route('assets.show', [$asset, 'tab' => 'connections']));

        $this->assertSame('HDMI 1', $interface->fresh()->name);
        $this->assertSame($network->id, $interface->fresh()->connection_type_id);

        $this->actingAs($user)
            ->delete(route('assets.interfaces.destroy', [$asset, $interface]))
            ->assertRedirect(route('assets.show', [$asset, 'tab' => 'connections']));

        $this->assertDatabaseMissing('asset_interfaces', ['id' => $interface->id]);
    }

    public function test_connection_create_uses_canonical_ordering_and_derives_type_from_interface(): void
    {
        $user = $this->manager();
        $assetA = $this->makeAsset(name: 'Switch');
        $assetB = $this->makeAsset(name: 'Encoder');
        $network = ConnectionType::query()->where('slug', 'network')->firstOrFail();
        $hdmi = ConnectionType::query()->where('slug', 'hdmi')->firstOrFail();
        $ifaceA = AssetInterface::factory()->create([
            'asset_id' => $assetA->id,
            'name' => 'Port 1',
            'connection_type_id' => $network->id,
        ]);
        $ifaceB = AssetInterface::factory()->create([
            'asset_id' => $assetB->id,
            'name' => 'In 1',
            'connection_type_id' => $network->id,
        ]);
        $ifaceAHdmi = AssetInterface::factory()->create([
            'asset_id' => $assetA->id,
            'name' => 'HDMI Out',
            'connection_type_id' => $hdmi->id,
        ]);
        $ifaceBHdmi = AssetInterface::factory()->create([
            'asset_id' => $assetB->id,
            'name' => 'HDMI In',
            'connection_type_id' => $hdmi->id,
        ]);

        $this->actingAs($user)
            ->post(route('assets.connections.store', $assetA), [
                'local_interface_id' => $ifaceA->id,
                'peer_asset_id' => $assetB->id,
                'peer_interface_id' => $ifaceB->id,
                'label' => 'Primary',
            ])
            ->assertRedirect(route('assets.show', [$assetA, 'tab' => 'connections']));

        $first = AssetConnection::query()->first();
        $this->assertNotNull($first);
        $this->assertSame(min($ifaceA->id, $ifaceB->id), $first->interface_a_id);
        $this->assertSame(max($ifaceA->id, $ifaceB->id), $first->interface_b_id);
        $this->assertSame($network->id, $first->connection_type_id);

        $this->actingAs($user)
            ->post(route('assets.connections.store', $assetA), [
                'local_interface_id' => $ifaceAHdmi->id,
                'peer_asset_id' => $assetB->id,
                'peer_interface_id' => $ifaceBHdmi->id,
            ])
            ->assertRedirect(route('assets.show', [$assetA, 'tab' => 'connections']));

        $this->assertSame(2, AssetConnection::query()->count());
        $this->assertDatabaseHas('asset_connections', [
            'connection_type_id' => $hdmi->id,
        ]);
    }

    public function test_connection_can_create_missing_peer_interface(): void
    {
        $user = $this->manager();
        $assetA = $this->makeAsset(name: 'Switch');
        $assetB = $this->makeAsset(name: 'Encoder');
        $network = ConnectionType::query()->where('slug', 'network')->firstOrFail();
        $ifaceA = AssetInterface::factory()->create([
            'asset_id' => $assetA->id,
            'name' => 'Port 1',
            'connection_type_id' => $network->id,
        ]);

        $this->assertSame(0, $assetB->interfaces()->count());

        $this->actingAs($user)
            ->post(route('assets.connections.store', $assetA), [
                'local_interface_id' => $ifaceA->id,
                'peer_asset_id' => $assetB->id,
                'new_peer_interface_name' => 'Uplink',
                'label' => 'Trunk',
            ])
            ->assertRedirect(route('assets.show', [$assetA, 'tab' => 'connections']));

        $peerIface = $assetB->interfaces()->where('name', 'Uplink')->first();
        $this->assertNotNull($peerIface);
        $this->assertSame($network->id, $peerIface->connection_type_id);
        $this->assertDatabaseHas('asset_connections', [
            'connection_type_id' => $network->id,
            'label' => 'Trunk',
        ]);
    }

    public function test_custom_protocol_is_required_when_type_allows_protocol(): void
    {
        $user = $this->manager();
        $assetA = $this->makeAsset(name: 'A');
        $assetB = $this->makeAsset(name: 'B');
        $custom = ConnectionType::query()->where('slug', 'custom-protocol')->firstOrFail();
        $ifaceA = AssetInterface::factory()->create([
            'asset_id' => $assetA->id,
            'connection_type_id' => $custom->id,
        ]);
        $ifaceB = AssetInterface::factory()->create([
            'asset_id' => $assetB->id,
            'connection_type_id' => $custom->id,
        ]);

        $this->actingAs($user)
            ->from(route('assets.show', [$assetA, 'tab' => 'connections']))
            ->post(route('assets.connections.store', $assetA), [
                'local_interface_id' => $ifaceA->id,
                'peer_asset_id' => $assetB->id,
                'peer_interface_id' => $ifaceB->id,
            ])
            ->assertSessionHasErrors('protocol');

        $this->actingAs($user)
            ->post(route('assets.connections.store', $assetA), [
                'local_interface_id' => $ifaceA->id,
                'peer_asset_id' => $assetB->id,
                'peer_interface_id' => $ifaceB->id,
                'protocol' => 'NDI|HX',
            ])
            ->assertRedirect(route('assets.show', [$assetA, 'tab' => 'connections']));

        $this->assertDatabaseHas('asset_connections', [
            'connection_type_id' => $custom->id,
            'protocol' => 'NDI|HX',
        ]);
    }

    public function test_connection_type_seeder_includes_xlr_dmx_power_and_usb(): void
    {
        $expected = [
            'xlr' => 'xlr',
            'dmx' => 'dmx',
            'power' => 'power',
            'usb' => 'usb',
        ];

        foreach ($expected as $slug => $icon) {
            $type = ConnectionType::query()->where('slug', $slug)->first();
            $this->assertNotNull($type, "Missing seeded connection type [{$slug}]");
            $this->assertSame($icon, $type->icon);
        }
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
                'icon' => 'xlr',
                'allows_protocol' => '0',
                'sort_order' => 110,
            ])
            ->assertRedirect(route('connection-types.index'));

        $type = ConnectionType::query()->where('slug', 'fiber')->first();
        $this->assertNotNull($type);
        $this->assertSame('xlr', $type->icon);

        $this->actingAs($manager)
            ->put(route('connection-types.update', $type), [
                'name' => 'Fiber Optic',
                'color' => '#445566',
                'icon' => 'usb',
                'allows_protocol' => '0',
                'sort_order' => 110,
            ])
            ->assertRedirect(route('connection-types.index'));

        $this->assertSame('Fiber Optic', $type->fresh()->name);
        $this->assertSame('usb', $type->fresh()->icon);
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
        $graphNames = collect($response->viewData('graph')['nodes'] ?? [])
            ->map(fn (array $node): string => (string) ($node['data']['name'] ?? ''))
            ->filter()
            ->values()
            ->all();

        $this->assertSame(['Router', 'Switch'], $graphNames);
        $this->assertNotContains('Spare', $graphNames);
    }

    public function test_connections_tab_lists_peer_assets(): void
    {
        $user = $this->scanner();
        $assetA = $this->makeAsset(name: 'Display');
        $assetB = $this->makeAsset(name: 'Matrix');
        $hdmi = ConnectionType::query()->where('slug', 'hdmi')->firstOrFail();
        $ifaceA = AssetInterface::factory()->create([
            'asset_id' => $assetA->id,
            'name' => 'HDMI In',
            'connection_type_id' => $hdmi->id,
        ]);
        $ifaceB = AssetInterface::factory()->create([
            'asset_id' => $assetB->id,
            'name' => 'Out 3',
            'connection_type_id' => $hdmi->id,
        ]);

        app(AssetConnectionService::class)->create($ifaceA, $ifaceB, $hdmi, ['label' => 'Program']);

        $this->actingAs($user)
            ->get(route('assets.show', [$assetA, 'tab' => 'connections']))
            ->assertOk()
            ->assertSee('Matrix', false)
            ->assertSee('Out 3', false)
            ->assertSee('Program', false)
            ->assertSee('Create new peer interface', false)
            ->assertSee(route('assets.show', [$assetB, 'tab' => 'connections']), false)
            ->assertSee('View connection map', false)
            ->assertSee(route('reports.connection-map', ['asset_id' => $assetA->id]), false);
    }

    public function test_connections_tab_hides_map_link_without_connections(): void
    {
        $user = $this->scanner();
        $asset = $this->makeAsset(name: 'Lonely');

        $this->actingAs($user)
            ->get(route('assets.show', [$asset, 'tab' => 'connections']))
            ->assertOk()
            ->assertDontSee('View connection map', false);
    }

    public function test_map_filters_to_connected_component_from_asset(): void
    {
        $user = $this->manager();
        $type = $this->makeItemType();
        $assetA = $this->makeAsset($type, name: 'Focus Hub');
        $assetB = $this->makeAsset($type, name: 'Peer One');
        $assetC = $this->makeAsset($type, name: 'Peer Two');
        $assetD = $this->makeAsset($type, name: 'Other Island');
        $assetE = $this->makeAsset($type, name: 'Other Peer');

        $hdmi = ConnectionType::query()->where('slug', 'hdmi')->firstOrFail();
        $network = ConnectionType::query()->where('slug', 'network')->firstOrFail();

        $ifaceA1 = AssetInterface::factory()->create(['asset_id' => $assetA->id, 'name' => 'Out 1']);
        $ifaceA2 = AssetInterface::factory()->create(['asset_id' => $assetA->id, 'name' => 'Out 2']);
        $ifaceB = AssetInterface::factory()->create(['asset_id' => $assetB->id, 'name' => 'In']);
        $ifaceC = AssetInterface::factory()->create(['asset_id' => $assetC->id, 'name' => 'In']);
        $ifaceD = AssetInterface::factory()->create(['asset_id' => $assetD->id, 'name' => 'LAN']);
        $ifaceE = AssetInterface::factory()->create(['asset_id' => $assetE->id, 'name' => 'LAN']);

        $service = app(AssetConnectionService::class);
        $service->create($ifaceA1, $ifaceB, $hdmi);
        $service->create($ifaceA2, $ifaceC, $hdmi);
        $service->create($ifaceD, $ifaceE, $network);

        $response = $this->actingAs($user)->get(route('reports.connection-map', [
            'asset_id' => $assetA->id,
        ]));

        $response->assertOk();
        $response->assertSee('Focus Hub', false);
        $response->assertSee('Peer One', false);
        $response->assertSee('Peer Two', false);
        $response->assertSee('Focused on', false);
        $response->assertDontSee('Other Island', false);
        $response->assertDontSee('Other Peer', false);

        $graph = $service->buildGraph(['asset_id' => $assetA->id]);
        $this->assertSame($assetA->id, $graph['focus_asset_id']);
        $this->assertSame(3, $graph['asset_count']);
        $this->assertCount(3, $graph['nodes']);
        $this->assertCount(2, $graph['edges']);
        $focusNode = collect($graph['nodes'])->first(
            fn (array $node): bool => (int) $node['data']['asset_id'] === $assetA->id
                && ($node['data']['kind'] ?? null) === 'asset'
        );
        $this->assertNotNull($focusNode);
        $this->assertTrue($focusNode['data']['focus']);
        $this->assertSame('focus', $focusNode['classes'] ?? null);
    }

    public function test_build_graph_includes_room_rack_serial_and_tp_barcode_on_nodes(): void
    {
        $room = Location::query()->create([
            'name' => 'Control Room',
            'type' => LocationType::Room,
        ]);
        $rack = Location::query()->create([
            'name' => 'Rack A',
            'type' => LocationType::Rack,
            'parent_id' => $room->id,
        ]);

        $type = $this->makeItemType();
        $assetA = $this->makeAsset($type, $rack, 'Encoder');
        $assetA->update([
            'serial_number' => 'SN-4242',
            'tp_barcode' => 'TP-9001',
        ]);
        $assetB = $this->makeAsset($type, $rack, 'Decoder');

        $hdmi = ConnectionType::query()->where('slug', 'hdmi')->firstOrFail();
        $ifaceA = AssetInterface::factory()->create(['asset_id' => $assetA->id, 'name' => 'Out']);
        $ifaceB = AssetInterface::factory()->create(['asset_id' => $assetB->id, 'name' => 'In']);
        app(AssetConnectionService::class)->create($ifaceA, $ifaceB, $hdmi);

        $graph = app(AssetConnectionService::class)->buildGraph();
        $node = collect($graph['nodes'])->first(
            fn (array $node): bool => (int) ($node['data']['asset_id'] ?? 0) === $assetA->id
                && ($node['data']['kind'] ?? null) === 'asset'
        );

        $this->assertNotNull($node);
        $this->assertSame('Control Room / Rack A', $node['data']['room_rack']);
        $this->assertSame('SN-4242', $node['data']['serial_number']);
        $this->assertSame('TP-9001', $node['data']['tp_barcode']);
    }

    public function test_build_graph_uses_compound_nodes_when_asset_has_multiple_connection_types(): void
    {
        $type = $this->makeItemType();
        $hub = $this->makeAsset($type, name: 'Multi Hub');
        $hdmiPeer = $this->makeAsset($type, name: 'HDMI Peer');
        $netPeer = $this->makeAsset($type, name: 'Net Peer');

        $hdmi = ConnectionType::query()->where('slug', 'hdmi')->firstOrFail();
        $network = ConnectionType::query()->where('slug', 'network')->firstOrFail();

        $hubHdmi = AssetInterface::factory()->create(['asset_id' => $hub->id, 'name' => 'HDMI Out']);
        $hubNet = AssetInterface::factory()->create(['asset_id' => $hub->id, 'name' => 'LAN']);
        $peerHdmi = AssetInterface::factory()->create(['asset_id' => $hdmiPeer->id, 'name' => 'In']);
        $peerNet = AssetInterface::factory()->create(['asset_id' => $netPeer->id, 'name' => 'LAN']);

        $service = app(AssetConnectionService::class);
        $service->create($hubHdmi, $peerHdmi, $hdmi);
        $service->create($hubNet, $peerNet, $network);

        $graph = $service->buildGraph();
        $this->assertSame(3, $graph['asset_count']);
        $this->assertCount(2, $graph['edges']);

        $hubNodes = collect($graph['nodes'])->filter(
            fn (array $node): bool => (int) $node['data']['asset_id'] === $hub->id
        );
        $this->assertCount(3, $hubNodes);

        $parent = $hubNodes->first(fn (array $node): bool => ($node['data']['kind'] ?? null) === 'asset');
        $this->assertNotNull($parent);
        $this->assertTrue($parent['data']['compound']);
        $this->assertSame('asset-'.$hub->id, $parent['data']['id']);

        $ports = $hubNodes->filter(fn (array $node): bool => ($node['data']['kind'] ?? null) === 'type-port');
        $this->assertCount(2, $ports);
        $this->assertTrue($ports->every(
            fn (array $node): bool => ($node['data']['parent'] ?? null) === 'asset-'.$hub->id
        ));

        $edgeSources = collect($graph['edges'])->pluck('data.source')->all();
        $edgeTargets = collect($graph['edges'])->pluck('data.target')->all();
        $this->assertContains('asset-'.$hub->id.'-type-'.$hdmi->id, [...$edgeSources, ...$edgeTargets]);
        $this->assertContains('asset-'.$hub->id.'-type-'.$network->id, [...$edgeSources, ...$edgeTargets]);
    }

    public function test_connection_create_and_remove_appear_on_both_asset_histories(): void
    {
        $user = $this->manager();
        $assetA = $this->makeAsset(name: 'Display');
        $assetB = $this->makeAsset(name: 'Matrix');
        $hdmi = ConnectionType::query()->where('slug', 'hdmi')->firstOrFail();
        $ifaceA = AssetInterface::factory()->create([
            'asset_id' => $assetA->id,
            'name' => 'HDMI In',
            'connection_type_id' => $hdmi->id,
        ]);
        $ifaceB = AssetInterface::factory()->create([
            'asset_id' => $assetB->id,
            'name' => 'Out 3',
            'connection_type_id' => $hdmi->id,
        ]);

        $this->actingAs($user)
            ->post(route('assets.connections.store', $assetA), [
                'local_interface_id' => $ifaceA->id,
                'peer_asset_id' => $assetB->id,
                'peer_interface_id' => $ifaceB->id,
                'label' => 'Program',
            ])
            ->assertRedirect(route('assets.show', [$assetA, 'tab' => 'connections']));

        $connection = AssetConnection::query()->first();
        $this->assertNotNull($connection);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Asset::class,
            'auditable_id' => $assetA->id,
            'action' => 'connection_created',
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Asset::class,
            'auditable_id' => $assetB->id,
            'action' => 'connection_created',
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('assets.show', [$assetA, 'tab' => 'history']))
            ->assertOk()
            ->assertSee('connection created', false)
            ->assertSee('Connected to Matrix', false)
            ->assertSee('HDMI In', false)
            ->assertSee('Out 3', false)
            ->assertSee('Program', false);

        $this->actingAs($user)
            ->get(route('assets.show', [$assetB, 'tab' => 'history']))
            ->assertOk()
            ->assertSee('Connected to Display', false);

        $this->actingAs($user)
            ->delete(route('assets.connections.destroy', [$assetA, $connection]))
            ->assertRedirect(route('assets.show', [$assetA, 'tab' => 'connections']));

        $this->assertDatabaseMissing('asset_connections', ['id' => $connection->id]);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Asset::class,
            'auditable_id' => $assetA->id,
            'action' => 'connection_removed',
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Asset::class,
            'auditable_id' => $assetB->id,
            'action' => 'connection_removed',
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('assets.show', [$assetA, 'tab' => 'history']))
            ->assertOk()
            ->assertSee('connection removed', false)
            ->assertSee('Disconnected from Matrix', false);
    }

    public function test_peer_interfaces_endpoint_prioritises_matching_connection_type(): void
    {
        $user = $this->manager();
        $asset = $this->makeAsset(name: 'Local');
        $peer = $this->makeAsset(name: 'Peer');
        $network = ConnectionType::query()->where('slug', 'network')->firstOrFail();
        $hdmi = ConnectionType::query()->where('slug', 'hdmi')->firstOrFail();

        $hdmiIface = AssetInterface::factory()->create([
            'asset_id' => $peer->id,
            'name' => 'HDMI Port',
            'connection_type_id' => $hdmi->id,
            'sort_order' => 10,
        ]);
        $networkIface = AssetInterface::factory()->create([
            'asset_id' => $peer->id,
            'name' => 'LAN',
            'connection_type_id' => $network->id,
            'sort_order' => 20,
        ]);

        $response = $this->actingAs($user)->getJson(
            route('assets.peer-interfaces', [$asset, $peer]).'?connection_type_id='.$network->id
        );

        $response->assertOk();
        $ids = collect($response->json('interfaces'))->pluck('id')->all();
        $this->assertSame([$networkIface->id, $hdmiIface->id], $ids);
        $this->assertTrue($response->json('interfaces.0.matches_type'));
        $this->assertFalse($response->json('interfaces.1.matches_type'));
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

    public function test_manufacturer_model_form_can_persist_interface_templates(): void
    {
        $user = $this->manager();
        $manufacturer = Manufacturer::factory()->create();

        $this->actingAs($user)
            ->post(route('manufacturers.models.store', $manufacturer), [
                'name' => 'X100',
                'interface_templates' => [
                    ['name' => 'HDMI 1', 'notes' => ''],
                    ['name' => 'USB-C', 'notes' => 'Host'],
                ],
            ])
            ->assertRedirect(route('manufacturers.index'));

        $model = ManufacturerModel::query()->where('name', 'X100')->firstOrFail();
        $this->assertEqualsCanonicalizing(
            ['HDMI 1', 'USB-C'],
            $model->interfaceTemplates()->pluck('name')->all()
        );
    }

    public function test_sync_missing_templates_for_manufacturer_model_assets(): void
    {
        $user = $this->manager();
        $manufacturer = Manufacturer::factory()->create();
        $model = ManufacturerModel::factory()->create(['manufacturer_id' => $manufacturer->id]);
        $itemType = $this->makeItemType();
        $asset = Asset::query()->create([
            'item_type_id' => $itemType->id,
            'manufacturer_model_id' => $model->id,
            'name' => 'Unit A',
            'status' => AssetStatus::Available,
        ]);

        ManufacturerModelInterfaceTemplate::query()->create([
            'manufacturer_model_id' => $model->id,
            'name' => 'Model Port',
            'sort_order' => 10,
        ]);

        $this->assertSame(0, $asset->interfaces()->count());

        $this->actingAs($user)
            ->post(route('manufacturers.models.sync-interfaces', [$manufacturer, $model]))
            ->assertRedirect(route('manufacturers.models.edit', [$manufacturer, $model]));

        $this->assertSame(['Model Port'], $asset->fresh()->interfaces()->pluck('name')->all());
    }

    public function test_merge_type_and_model_templates_skips_duplicate_names(): void
    {
        $user = $this->manager();
        $itemType = $this->makeItemType('Amplifier');
        $manufacturer = Manufacturer::factory()->create();
        $model = ManufacturerModel::factory()->create(['manufacturer_id' => $manufacturer->id]);

        ItemTypeInterfaceTemplate::query()->create([
            'item_type_id' => $itemType->id,
            'name' => 'LAN 1',
            'sort_order' => 10,
        ]);
        ItemTypeInterfaceTemplate::query()->create([
            'item_type_id' => $itemType->id,
            'name' => 'AES',
            'sort_order' => 20,
        ]);
        ManufacturerModelInterfaceTemplate::query()->create([
            'manufacturer_model_id' => $model->id,
            'name' => 'lan 1',
            'sort_order' => 10,
        ]);
        ManufacturerModelInterfaceTemplate::query()->create([
            'manufacturer_model_id' => $model->id,
            'name' => 'Dante',
            'sort_order' => 20,
        ]);

        $this->actingAs($user)->post(route('assets.store'), [
            'name' => 'Amp 1',
            'item_type_id' => $itemType->id,
            'manufacturer_model_id' => $model->id,
            'status' => AssetStatus::Available->value,
            'quantity' => 1,
        ]);

        $asset = Asset::query()->where('name', 'Amp 1')->firstOrFail();
        $this->assertEqualsCanonicalizing(
            ['LAN 1', 'AES', 'Dante'],
            $asset->interfaces()->pluck('name')->all()
        );
    }

    public function test_template_connection_type_is_copied_when_present(): void
    {
        $user = $this->manager();
        $itemType = $this->makeItemType();
        $network = ConnectionType::query()->where('slug', 'network')->firstOrFail();
        $hdmi = ConnectionType::query()->where('slug', 'hdmi')->firstOrFail();

        ItemTypeInterfaceTemplate::query()->create([
            'item_type_id' => $itemType->id,
            'name' => 'LAN',
            'sort_order' => 10,
            'connection_type_id' => $network->id,
        ]);
        ItemTypeInterfaceTemplate::query()->create([
            'item_type_id' => $itemType->id,
            'name' => 'Aux',
            'sort_order' => 20,
            'connection_type_id' => null,
        ]);

        $this->actingAs($user)->post(route('assets.store'), [
            'name' => 'Device',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available->value,
            'quantity' => 1,
        ]);

        $asset = Asset::query()->where('name', 'Device')->firstOrFail();
        $lan = $asset->interfaces()->where('name', 'LAN')->first();
        $aux = $asset->interfaces()->where('name', 'Aux')->first();

        $this->assertSame((int) $network->id, (int) $lan->connection_type_id);
        $this->assertNull($aux->connection_type_id);

        $manufacturer = Manufacturer::factory()->create();
        $model = ManufacturerModel::factory()->create(['manufacturer_id' => $manufacturer->id]);
        ManufacturerModelInterfaceTemplate::query()->create([
            'manufacturer_model_id' => $model->id,
            'name' => 'HDMI Out',
            'sort_order' => 10,
            'connection_type_id' => $hdmi->id,
        ]);

        $this->actingAs($user)->put(route('assets.update', $asset), [
            'name' => $asset->name,
            'item_type_id' => $itemType->id,
            'manufacturer_model_id' => $model->id,
            'status' => AssetStatus::Available->value,
            'quantity' => 1,
        ]);

        $hdmiIface = $asset->fresh()->interfaces()->where('name', 'HDMI Out')->first();
        $this->assertNotNull($hdmiIface);
        $this->assertSame((int) $hdmi->id, (int) $hdmiIface->connection_type_id);
    }

    public function test_apply_missing_templates_from_connections_tab(): void
    {
        $user = $this->manager();
        $itemType = $this->makeItemType();
        $asset = $this->makeAsset($itemType, name: 'Switch');

        ItemTypeInterfaceTemplate::query()->create([
            'item_type_id' => $itemType->id,
            'name' => 'SFP+ 1',
            'sort_order' => 10,
        ]);

        $this->actingAs($user)
            ->post(route('assets.interfaces.apply-templates', $asset))
            ->assertRedirect(route('assets.show', [$asset, 'tab' => 'connections']));

        $this->assertSame(['SFP+ 1'], $asset->fresh()->interfaces()->pluck('name')->all());

        $this->actingAs($user)
            ->post(route('assets.interfaces.store', $asset), [
                'name' => 'Manual Port',
                'connection_type_id' => ConnectionType::query()->where('slug', 'network')->value('id'),
            ])
            ->assertRedirect(route('assets.show', [$asset, 'tab' => 'connections']));

        $this->assertCount(2, $asset->fresh()->interfaces);
    }

    public function test_scanner_cannot_apply_interface_templates(): void
    {
        $user = $this->scanner();
        $asset = $this->makeAsset();

        $this->actingAs($user)
            ->post(route('assets.interfaces.apply-templates', $asset))
            ->assertForbidden();
    }
}
