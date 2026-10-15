<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Enums\StockMovementType;
use App\Models\Asset;
use App\Models\AssetStockToken;
use App\Models\CustomFieldDefinition;
use App\Models\CustomFieldSet;
use App\Models\ItemType;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\KanbanTagService;
use App\Services\StockChartService;
use App\Services\StockMovementService;
use App\Support\StockTracking;
use Database\Seeders\ItemTypeSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StockConsumablesFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    protected function withFeature(User $user): User
    {
        $user->preferences = [
            'features' => [
                StockTracking::FEATURE_KEY => true,
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

    protected function stockType(bool $supports = true): ItemType
    {
        return ItemType::factory()->create([
            'supports_stock_tracking' => $supports,
        ]);
    }

    protected function stockAsset(?ItemType $type = null, int $quantity = 10): Asset
    {
        $type ??= $this->stockType();

        return Asset::factory()->create([
            'item_type_id' => $type->id,
            'status' => AssetStatus::Available,
            'quantity' => $quantity,
            'min_stock_level' => 5,
            'max_stock_level' => 40,
        ]);
    }

    public function test_feature_flag_off_hides_stock_tab_and_blocks_routes(): void
    {
        $user = $this->manager(feature: false);
        $asset = $this->stockAsset();

        $this->actingAs($user)
            ->get(route('assets.show', [$asset, 'tab' => 'stock']))
            ->assertOk()
            ->assertDontSee('Record stock movement', false)
            ->assertDontSee('tab=stock', false);

        $this->actingAs($user)
            ->post(route('assets.stock-movements.store', $asset), [
                'type' => 'count',
                'quantity' => 8,
            ])
            ->assertNotFound();
    }

    public function test_stock_tab_hidden_when_item_type_does_not_support_tracking(): void
    {
        $user = $this->manager();
        $asset = $this->stockAsset($this->stockType(supports: false));

        $this->actingAs($user)
            ->get(route('assets.show', [$asset, 'tab' => 'stock']))
            ->assertOk()
            ->assertDontSee('Record stock movement', false)
            ->assertDontSee('tab=stock', false);
    }

    public function test_stock_tab_visible_when_feature_and_type_enabled(): void
    {
        $user = $this->manager();
        $asset = $this->stockAsset();

        $this->actingAs($user)
            ->get(route('assets.show', [$asset, 'tab' => 'stock']))
            ->assertOk()
            ->assertSee('Record stock movement', false)
            ->assertSee('On hand', false)
            ->assertSee('Kanban tags', false);
    }

    public function test_count_restock_and_breakage_update_quantity_and_ledger(): void
    {
        $user = $this->scanner();
        $asset = $this->stockAsset(quantity: 10);
        $service = app(StockMovementService::class);

        $service->record($asset, [
            'type' => StockMovementType::Count,
            'quantity' => 8,
        ], $user);
        $asset->refresh();
        $this->assertSame(8, (int) $asset->quantity);

        $service->record($asset, [
            'type' => StockMovementType::Restock,
            'quantity' => 5,
            'external_po_number' => 'PO-100',
            'external_po_url' => 'https://momentous.example/po/100',
        ], $user);
        $asset->refresh();
        $this->assertSame(13, (int) $asset->quantity);

        $service->record($asset, [
            'type' => StockMovementType::Breakage,
            'quantity' => 3,
            'notes' => 'Cracked housings',
        ], $user);
        $asset->refresh();
        $this->assertSame(10, (int) $asset->quantity);

        $this->assertDatabaseHas('stock_movements', [
            'asset_id' => $asset->id,
            'type' => 'restock',
            'external_po_number' => 'PO-100',
            'external_po_url' => 'https://momentous.example/po/100',
            'quantity_after' => 13,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Asset::class,
            'auditable_id' => $asset->id,
            'action' => 'stock_restock',
        ]);
    }

    public function test_cannot_go_negative_on_breakage(): void
    {
        $user = $this->scanner();
        $asset = $this->stockAsset(quantity: 2);

        $this->actingAs($user)
            ->post(route('assets.stock-movements.store', $asset), [
                'type' => 'breakage',
                'quantity' => 5,
                'notes' => 'Too many',
            ])
            ->assertSessionHasErrors('quantity');

        $this->assertSame(2, (int) $asset->fresh()->quantity);
    }

    public function test_restock_can_attach_pdf_via_file_links(): void
    {
        Storage::fake((string) config('attachments.disk', 'local'));

        $user = $this->manager();
        $asset = $this->stockAsset(quantity: 4);
        $pdf = UploadedFile::fake()->create('po-200.pdf', 120, 'application/pdf');

        $this->actingAs($user)
            ->post(route('assets.stock-movements.store', $asset), [
                'type' => 'restock',
                'quantity' => 10,
                'external_po_number' => 'PO-200',
                'external_po_url' => 'https://momentous.example/po/200',
                'pdfs' => [$pdf],
            ])
            ->assertRedirect(route('assets.show', [$asset, 'tab' => 'stock', 'action' => 'restock']));

        $movement = StockMovement::query()
            ->where('asset_id', $asset->id)
            ->where('type', 'restock')
            ->latest('id')
            ->first();

        $this->assertNotNull($movement);
        $this->assertSame(1, $movement->files()->count());
        $this->assertSame(14, (int) $asset->fresh()->quantity);
    }

    public function test_chart_payload_includes_markers_trend_and_po_detail(): void
    {
        $user = $this->manager();
        $asset = $this->stockAsset(quantity: 20);
        $service = app(StockMovementService::class);

        $service->record($asset->fresh(), [
            'type' => StockMovementType::Count,
            'quantity' => 30,
            'recorded_at' => now()->subDays(12),
        ], $user);

        $service->record($asset->fresh(), [
            'type' => StockMovementType::Breakage,
            'quantity' => 8,
            'notes' => 'Wear',
            'recorded_at' => now()->subDays(8),
        ], $user);

        $service->record($asset->fresh(), [
            'type' => StockMovementType::Breakage,
            'quantity' => 7,
            'notes' => 'More wear',
            'recorded_at' => now()->subDays(4),
        ], $user);

        $restock = $service->record($asset->fresh(), [
            'type' => StockMovementType::Restock,
            'quantity' => 1,
            'external_po_number' => 'PO-TREND',
            'external_po_url' => 'https://momentous.example/po/trend',
            'recorded_at' => now()->subDay(),
        ], $user);

        $payload = app(StockChartService::class)->build($asset->fresh());

        $this->assertSame(5, $payload['min_line']);
        $this->assertSame(40, $payload['max_line']);
        $this->assertNotEmpty($payload['points']);
        $this->assertNotEmpty($payload['restock_markers']);
        $this->assertNotNull($payload['trend']);

        $detail = app(StockChartService::class)->pointPayload($restock);
        $this->assertSame('PO-TREND', $detail['external_po_number']);
        $this->assertSame('https://momentous.example/po/trend', $detail['external_po_url']);
    }

    public function test_guest_token_can_mutate_only_that_asset_and_revoked_token_404s(): void
    {
        $asset = $this->stockAsset(quantity: 12);
        $other = $this->stockAsset(quantity: 99);
        $kanban = app(KanbanTagService::class);
        $created = $kanban->rotate($asset, 'kanban-a');

        $this->post(route('stock.guest.store', ['token' => $created['plain_token']]), [
            'type' => 'count',
            'quantity' => 9,
        ])->assertRedirect();

        $this->assertSame(9, (int) $asset->fresh()->quantity);
        $this->assertSame(99, (int) $other->fresh()->quantity);

        $this->get(route('stock.guest.show', ['token' => $created['plain_token']]))
            ->assertOk()
            ->assertSee($asset->name, false);

        $kanban->revoke($created['token']);

        $this->get(route('stock.guest.show', ['token' => $created['plain_token']]))
            ->assertNotFound();
    }

    public function test_guest_stock_routes_return_404_when_kill_switch_disabled(): void
    {
        config(['app.stock_guest_enabled' => false]);

        $asset = $this->stockAsset(quantity: 12);
        $created = app(KanbanTagService::class)->rotate($asset, 'kanban-a');

        $this->get(route('stock.guest.show', ['token' => $created['plain_token']]))
            ->assertNotFound();

        $this->post(route('stock.guest.store', ['token' => $created['plain_token']]), [
            'type' => 'count',
            'quantity' => 8,
        ])->assertNotFound();

        $this->assertSame(12, (int) $asset->fresh()->quantity);
    }

    public function test_login_path_records_stock_movement(): void
    {
        $user = $this->scanner();
        $asset = $this->stockAsset(quantity: 6);

        $this->actingAs($user)
            ->post(route('assets.stock-movements.store', $asset), [
                'type' => 'restock',
                'quantity' => 4,
                'external_po_number' => 'PO-LOGIN',
            ])
            ->assertRedirect(route('assets.show', [$asset, 'tab' => 'stock', 'action' => 'restock']));

        $this->assertSame(10, (int) $asset->fresh()->quantity);
    }

    public function test_kanban_token_rotate_and_revoke(): void
    {
        $user = $this->manager();
        $asset = $this->stockAsset();

        $this->actingAs($user)
            ->post(route('assets.stock-tokens.rotate', $asset), [
                'label' => 'kanban-a',
            ])
            ->assertRedirect(route('assets.show', [$asset, 'tab' => 'stock']))
            ->assertSessionHas('kanban_guest_url');

        $token = AssetStockToken::query()->where('asset_id', $asset->id)->whereNull('revoked_at')->first();
        $this->assertNotNull($token);

        $this->actingAs($user)
            ->delete(route('assets.stock-tokens.revoke', [$asset, $token]))
            ->assertRedirect(route('assets.show', [$asset, 'tab' => 'stock']));

        $this->assertNotNull($token->fresh()->revoked_at);
    }

    public function test_restocking_custom_field_set_is_seeded_and_attachable(): void
    {
        $this->seed(ItemTypeSeeder::class);

        $set = CustomFieldSet::query()->where('slug', 'restocking')->first();
        $this->assertNotNull($set);
        $this->assertTrue(
            CustomFieldDefinition::query()
                ->where('custom_field_set_id', $set->id)
                ->whereIn('slug', ['supplier', 'url', 'sku'])
                ->count() >= 3
        );

        $bulk = ItemType::query()->where('slug', 'bulk-item')->first();
        $this->assertNotNull($bulk);
        $this->assertTrue((bool) $bulk->supports_stock_tracking);
        $this->assertTrue($bulk->fieldSets()->where('slug', 'restocking')->exists());
    }

    public function test_stock_tracking_gate_helper_uses_developer_feature(): void
    {
        $user = User::factory()->create();
        $this->assertFalse(StockTracking::enabledFor($user));

        $this->withFeature($user);
        $this->assertTrue(StockTracking::enabledFor($user->fresh()));
    }
}
