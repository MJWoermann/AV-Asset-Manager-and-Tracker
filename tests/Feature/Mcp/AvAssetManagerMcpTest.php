<?php

namespace Tests\Feature\Mcp;

use App\Enums\CheckoutPurpose;
use App\Mcp\Servers\AvAssetManagerServer;
use App\Mcp\Tools\CheckoutAssetTool;
use App\Mcp\Tools\LookupAssetTool;
use App\Mcp\Tools\ScanCodeTool;
use App\Mcp\Tools\WhoamiTool;
use App\Models\Asset;
use App\Models\AssetList;
use App\Models\ItemType;
use App\Models\ScanSession;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvAssetManagerMcpTest extends TestCase
{
    use RefreshDatabase;

    protected ItemType $itemType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->itemType = ItemType::factory()->create([
            'name' => 'LX Fixture',
            'slug' => 'lx-fixture',
        ]);
    }

    protected function staffUser(string $role = 'scanner'): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_mcp_endpoint_denies_unauthenticated_requests(): void
    {
        $this->postJson('/mcp/av-assets', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
        ])->assertUnauthorized();
    }

    public function test_tool_denies_external_repairer_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('external_repairer');

        $response = AvAssetManagerServer::actingAs($user)->tool(WhoamiTool::class);

        $response->assertHasErrors();
        $response->assertSee('external_repairer');
    }

    public function test_lookup_asset_returns_compact_json(): void
    {
        $user = $this->staffUser('scanner');
        $this->actingAs($user);

        $asset = Asset::factory()->create([
            'item_type_id' => $this->itemType->id,
            'name' => 'MAC Aura',
            'tp_barcode' => 'TP-MCP-001',
            'fmi_ast' => 'FMI-MCP-001',
            'department_id' => $this->generalDepartment()->id,
        ]);

        $response = AvAssetManagerServer::actingAs($user)->tool(LookupAssetTool::class, [
            'code' => 'TP-MCP-001',
        ]);

        $response->assertOk();
        $response->assertSee('"found":true');
        $response->assertSee('"id":'.$asset->id);
        $response->assertSee('MAC Aura');
    }

    public function test_scan_code_records_against_active_session(): void
    {
        $user = $this->staffUser('scanner');
        $this->actingAs($user);

        $event = AssetList::create([
            'name' => 'MCP Event',
            'type' => 'event',
            'created_by' => $user->id,
            'department_id' => $this->generalDepartment()->id,
        ]);

        $session = ScanSession::create([
            'user_id' => $user->id,
            'department_id' => $this->generalDepartment()->id,
            'event_list_id' => $event->id,
            'status' => 'active',
        ]);

        $asset = Asset::factory()->create([
            'item_type_id' => $this->itemType->id,
            'name' => 'Scanned Fixture',
            'tp_barcode' => 'TP-SCAN-99',
            'department_id' => $this->generalDepartment()->id,
        ]);

        $response = AvAssetManagerServer::actingAs($user)->tool(ScanCodeTool::class, [
            'code' => 'TP-SCAN-99',
            'scan_session_id' => $session->id,
        ]);

        $response->assertOk();
        $response->assertSee('"unknown":false');
        $response->assertSee('Scanned Fixture');
        $this->assertDatabaseHas('asset_list_items', [
            'asset_list_id' => $event->id,
            'asset_id' => $asset->id,
        ]);
    }

    public function test_checkout_asset_via_service_tool(): void
    {
        $user = $this->staffUser('inventory_manager');
        $this->actingAs($user);

        $asset = Asset::factory()->create([
            'item_type_id' => $this->itemType->id,
            'name' => 'Hire Desk',
            'department_id' => $this->generalDepartment()->id,
        ]);

        $response = AvAssetManagerServer::actingAs($user)->tool(CheckoutAssetTool::class, [
            'asset_id' => $asset->id,
            'purpose' => CheckoutPurpose::Hire->value,
            'checked_out_to_name' => 'Guest Hiree',
            'notes' => 'MCP checkout',
        ]);

        $response->assertOk();
        $response->assertSee('Asset checked out');
        $this->assertDatabaseHas('asset_checkouts', [
            'asset_id' => $asset->id,
            'checked_out_to_name' => 'Guest Hiree',
            'purpose' => CheckoutPurpose::Hire->value,
        ]);
    }
}
