<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AuditLog;
use App\Models\ItemType;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    protected function scanner(): User
    {
        $user = User::factory()->create();
        $user->assignRole('scanner');

        return $user;
    }

    public function test_asset_show_includes_history_tab(): void
    {
        $user = $this->scanner();
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
        $user = $this->scanner();
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
        $user = $this->scanner();
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

    public function test_history_tab_filters_by_action(): void
    {
        $user = $this->scanner();
        $type = ItemType::create(['name' => 'LX Fixture', 'slug' => 'lx-fixture']);
        $asset = Asset::create([
            'name' => 'Filter Light',
            'item_type_id' => $type->id,
            'status' => AssetStatus::Available,
            'tp_barcode' => 'TP-A',
        ]);

        $this->actingAs($user);
        $asset->update(['tp_barcode' => 'TP-B']);

        $response = $this->actingAs($user)->get(route('assets.show', [
            $asset,
            'tab' => 'history',
            'action' => 'updated',
        ]));

        $response->assertOk();
        $response->assertSee('tp barcode');
        $response->assertSee('TP-A');
        $response->assertSee('TP-B');
        $response->assertDontSee('Asset created');
        $response->assertSee('searchableSelect(', false);
        $response->assertSee('autoSubmit: true', false);
        $response->assertSee('Clear all filters', false);
    }

    public function test_history_tab_filters_by_user(): void
    {
        $user = $this->scanner();
        $other = User::factory()->create();
        $other->assignRole('scanner');
        $type = ItemType::create(['name' => 'LX Fixture', 'slug' => 'lx-user-filter']);
        $asset = Asset::create([
            'name' => 'User Filter Light',
            'item_type_id' => $type->id,
            'status' => AssetStatus::Available,
        ]);

        AuditLog::create([
            'user_id' => $other->id,
            'auditable_type' => Asset::class,
            'auditable_id' => $asset->id,
            'action' => 'updated',
            'old_values' => ['name' => 'User Filter Light'],
            'new_values' => ['name' => 'Renamed By Other'],
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('assets.show', [
            $asset,
            'tab' => 'history',
            'user_id' => $other->id,
        ]));

        $response->assertOk();
        $response->assertSee('Renamed By Other');
        $response->assertSee($other->name);
        $response->assertDontSee('Asset created');
    }

    public function test_history_tab_filters_by_date_range(): void
    {
        $user = $this->scanner();
        $type = ItemType::create(['name' => 'LX Fixture', 'slug' => 'lx-date-filter']);
        $asset = Asset::create([
            'name' => 'Date Filter Light',
            'item_type_id' => $type->id,
            'status' => AssetStatus::Available,
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'auditable_type' => Asset::class,
            'auditable_id' => $asset->id,
            'action' => 'updated',
            'old_values' => ['name' => 'Date Filter Light'],
            'new_values' => ['name' => 'In Range Update'],
            'created_at' => '2025-06-15 10:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('assets.show', [
            $asset,
            'tab' => 'history',
            'date_from' => '2025-06-01',
            'date_to' => '2025-06-30',
        ]));

        $response->assertOk();
        $response->assertSee('In Range Update');
        $response->assertDontSee('Asset created');
    }

    public function test_history_tab_shows_empty_filter_message(): void
    {
        $user = $this->scanner();
        $type = ItemType::create(['name' => 'LX Fixture', 'slug' => 'lx-empty-filter']);
        $asset = Asset::create([
            'name' => 'Empty Filter Light',
            'item_type_id' => $type->id,
            'status' => AssetStatus::Available,
        ]);

        $response = $this->actingAs($user)->get(route('assets.show', [
            $asset,
            'tab' => 'history',
            'action' => 'deleted',
        ]));

        $response->assertOk();
        $response->assertSee('No history matches these filters.');
        $response->assertDontSee('No history recorded yet.');
    }
}
