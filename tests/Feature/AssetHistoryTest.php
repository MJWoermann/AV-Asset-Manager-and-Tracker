<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AuditLog;
use App\Models\ItemType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_asset_show_includes_history_tab(): void
    {
        $user = User::factory()->create();
        $type = ItemType::create(['name' => 'LX Fixture', 'slug' => 'lx-fixture']);
        $asset = Asset::create([
            'name' => 'Wash Light',
            'item_type_id' => $type->id,
            'status' => AssetStatus::Available,
        ]);

        $response = $this->actingAs($user)->get(route('assets.show', $asset));

        $response->assertOk();
        $response->assertSee('History');
        $response->assertSee('Details');
        $response->assertSee('Wash Light');
    }

    public function test_history_tab_lists_audit_entries_for_asset(): void
    {
        $user = User::factory()->create();
        $type = ItemType::create(['name' => 'LX Fixture', 'slug' => 'lx-fixture']);
        $asset = Asset::create([
            'name' => 'Spot Light',
            'item_type_id' => $type->id,
            'status' => AssetStatus::Available,
            'tp_barcode' => 'TP-OLD',
        ]);

        $this->actingAs($user);
        $asset->update(['tp_barcode' => 'TP-NEW']);

        $response = $this->actingAs($user)->get(route('assets.show', [$asset, 'tab' => 'history']));

        $response->assertOk();
        $response->assertSee('Asset created');
        $response->assertSee('updated');
        $response->assertSee('tp barcode');
        $response->assertSee('TP-OLD');
        $response->assertSee('TP-NEW');
        $response->assertSee($user->name);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Asset::class,
            'auditable_id' => $asset->id,
            'action' => 'updated',
        ]);
    }

    public function test_history_tab_does_not_show_unrelated_audit_logs(): void
    {
        $user = User::factory()->create();
        $type = ItemType::create(['name' => 'Audio', 'slug' => 'audio']);
        $asset = Asset::create([
            'name' => 'Mic A',
            'item_type_id' => $type->id,
            'status' => AssetStatus::Available,
        ]);
        $other = Asset::create([
            'name' => 'Mic B',
            'item_type_id' => $type->id,
            'status' => AssetStatus::Available,
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'auditable_type' => Asset::class,
            'auditable_id' => $other->id,
            'action' => 'updated',
            'old_values' => ['name' => 'Mic B'],
            'new_values' => ['name' => 'Mic B Renamed'],
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('assets.show', [$asset, 'tab' => 'history']));

        $response->assertOk();
        $response->assertDontSee('Mic B Renamed');
    }
}
