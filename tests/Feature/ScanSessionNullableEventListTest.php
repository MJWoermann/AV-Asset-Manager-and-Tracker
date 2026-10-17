<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetList;
use App\Models\AssetListItem;
use App\Models\ItemType;
use App\Models\ScanSession;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScanSessionNullableEventListTest extends TestCase
{
    use RefreshDatabase;

    protected ItemType $itemType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->itemType = ItemType::create([
            'name' => 'LX Fixture',
            'slug' => 'lx-fixture',
        ]);
    }

    protected function scanner(): User
    {
        $user = User::factory()->create();
        $user->assignRole('scanner');

        return $user;
    }

    protected function makeAsset(array $attributes = []): Asset
    {
        return Asset::create(array_merge([
            'name' => 'Test Asset',
            'item_type_id' => $this->itemType->id,
            'status' => AssetStatus::Available,
            'tp_barcode' => 'TP-SCAN-NONE-1',
        ], $attributes));
    }

    public function test_scan_page_offers_none_for_event_list(): void
    {
        $user = $this->scanner();
        AssetList::create([
            'name' => 'Event A',
            'type' => 'event',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('scan.index'));

        $response->assertOk();
        $response->assertSee('None', false);
        $response->assertDontSee('disabled selected>Select event list', false);
    }

    public function test_session_can_start_without_event_list(): void
    {
        $user = $this->scanner();

        $response = $this->actingAs($user)->post(route('scan.start'), [
            'event_list_id' => null,
            'inventory_list_id' => null,
            'asset_status' => AssetStatus::OnEvent->value,
        ]);

        $response->assertRedirect(route('scan.index'));
        $this->assertDatabaseHas('scan_sessions', [
            'user_id' => $user->id,
            'event_list_id' => null,
            'status' => 'active',
        ]);
    }

    public function test_scan_without_event_list_updates_asset_but_does_not_create_list_item(): void
    {
        $user = $this->scanner();
        $asset = $this->makeAsset([
            'name' => 'Moving Head',
            'tp_barcode' => 'TP-NONE-1',
            'status' => AssetStatus::Available,
        ]);

        $session = ScanSession::create([
            'user_id' => $user->id,
            'event_list_id' => null,
            'asset_status' => AssetStatus::OnEvent,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('scan.scan', $session), [
                'code' => 'TP-NONE-1',
            ]);

        $response->assertOk();
        $response->assertJsonPath('duplicate', false);
        $response->assertJsonPath('asset.name', 'Moving Head');
        $response->assertJsonPath('asset.url', route('assets.show', $asset));
        $this->assertNotEmpty($response->json('items'));
        $this->assertSame(AssetStatus::OnEvent, $asset->fresh()->status);
        $this->assertSame(0, AssetListItem::query()->count());
    }

    public function test_closing_session_without_event_list_returns_to_scan_page(): void
    {
        $user = $this->scanner();
        $session = ScanSession::create([
            'user_id' => $user->id,
            'event_list_id' => null,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post(route('scan.close', $session));

        $response->assertRedirect(route('scan.index'));
        $this->assertSame('closed', $session->fresh()->status);
    }

    public function test_active_session_can_clear_event_list_to_none(): void
    {
        $user = $this->scanner();
        $event = AssetList::create([
            'name' => 'Event A',
            'type' => 'event',
            'created_by' => $user->id,
        ]);

        $session = ScanSession::create([
            'user_id' => $user->id,
            'event_list_id' => $event->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->patch(route('scan.lists', $session), [
            'event_list_id' => null,
        ]);

        $response->assertRedirect(route('scan.index'));
        $this->assertNull($session->fresh()->event_list_id);
    }
}
