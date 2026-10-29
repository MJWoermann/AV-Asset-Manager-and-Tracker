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
use Laravel\Sanctum\Sanctum;
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
        return $this->enableFeature($user, StockTracking::FEATURE_KEY);
    }

    protected function manager(bool $feature = true): User
    {
        $user = User::factory()->create(['theme' => 'light']);
        $user->assignRole('inventory_manager');

        return $feature ? $this->withFeature($user) : $user;
    }

    protected function admin(bool $feature = true): User
    {
        $user = User::factory()->create(['theme' => 'light']);
        $user->assignRole('admin');

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

        $service->record($asset->fresh(), [
            'type' => StockMovementType::Breakage,
            'quantity' => 4,
            'notes' => 'Used after restock',
            'recorded_at' => now()->subHours(12),
        ], $user);

        $payload = app(StockChartService::class)->build($asset->fresh());

        $this->assertSame(5, $payload['min_line']);
        $this->assertSame(40, $payload['max_line']);
        $this->assertNotEmpty($payload['points']);
        $this->assertNotEmpty($payload['restock_markers']);
        $this->assertNotNull($payload['trend']);
        $projection = $payload['trend']['projection'];
        $this->assertSame($restock->recorded_at?->toIso8601String(), $projection[0]['x']);
        $this->assertGreaterThanOrEqual(0, $projection[0]['y']);
        $this->assertSame(0.0, (float) $projection[array_key_last($projection)]['y']);

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

    public function test_stock_movement_can_use_historical_recorded_at(): void
    {
        $user = $this->scanner();
        $asset = $this->stockAsset(quantity: 6);
        $recordedAt = now()->subDays(14)->toDateString();

        $this->actingAs($user)
            ->post(route('assets.stock-movements.store', $asset), [
                'type' => 'restock',
                'quantity' => 3,
                'recorded_at' => $recordedAt,
            ])
            ->assertRedirect(route('assets.show', [$asset, 'tab' => 'stock', 'action' => 'restock']));

        $this->assertSame(9, (int) $asset->fresh()->quantity);

        $movement = StockMovement::query()
            ->where('asset_id', $asset->id)
            ->where('type', StockMovementType::Restock)
            ->latest('id')
            ->first();

        $this->assertNotNull($movement);
        $this->assertSame($recordedAt, $movement->recorded_at?->toDateString());
    }

    public function test_stock_movement_rejects_future_recorded_at(): void
    {
        $user = $this->scanner();
        $asset = $this->stockAsset(quantity: 6);

        $this->actingAs($user)
            ->from(route('assets.show', [$asset, 'tab' => 'stock']))
            ->post(route('assets.stock-movements.store', $asset), [
                'type' => 'count',
                'quantity' => 6,
                'recorded_at' => now()->addDay()->toDateString(),
            ])
            ->assertRedirect(route('assets.show', [$asset, 'tab' => 'stock']))
            ->assertSessionHasErrors('recorded_at');

        $this->assertSame(6, (int) $asset->fresh()->quantity);
    }

    public function test_kanban_token_issue_shows_the_url_in_the_kanban_section(): void
    {
        $user = $this->manager();
        $asset = $this->stockAsset();

        $this->actingAs($user)
            ->followingRedirects()
            ->post(route('assets.stock-tokens.rotate', $asset), [
                'label' => 'kanban-a',
            ])
            ->assertOk()
            ->assertSeeInOrder([
                'Kanban tags',
                'New Kanban tag issued for kanban-a.',
                'New Kanban URL for kanban-a (shown once):',
                '/stock/t/',
            ], false);

        $token = AssetStockToken::query()->where('asset_id', $asset->id)->whereNull('revoked_at')->first();
        $this->assertNotNull($token);
        $this->assertSame('kanban-a', $token->label);
    }

    public function test_rotating_an_active_kanban_tag_is_rejected_until_it_is_revoked(): void
    {
        $user = $this->manager();
        $asset = $this->stockAsset();
        $stockUrl = route('assets.show', [$asset, 'tab' => 'stock']);

        $this->actingAs($user)
            ->post(route('assets.stock-tokens.rotate', $asset), [
                'label' => 'kanban-a',
            ])
            ->assertRedirect($stockUrl);

        $token = AssetStockToken::query()->where('asset_id', $asset->id)->whereNull('revoked_at')->first();
        $this->assertNotNull($token);

        $this->actingAs($user)
            ->from($stockUrl)
            ->post(route('assets.stock-tokens.rotate', $asset), [
                'label' => 'kanban-a',
            ])
            ->assertRedirect($stockUrl)
            ->assertSessionHasErrors([
                'label' => 'Revoke the active kanban-a tag before issuing a new one.',
            ]);

        $this->assertNull($token->fresh()->revoked_at);
        $this->assertSame(1, AssetStockToken::query()->where('asset_id', $asset->id)->count());

        $this->actingAs($user)
            ->delete(route('assets.stock-tokens.revoke', [$asset, $token]))
            ->assertRedirect($stockUrl)
            ->assertSessionHas('kanban_status', 'Kanban token revoked.');

        $this->assertNotNull($token->fresh()->revoked_at);

        $this->actingAs($user)
            ->post(route('assets.stock-tokens.rotate', $asset), [
                'label' => 'kanban-a',
            ])
            ->assertRedirect($stockUrl)
            ->assertSessionHas('kanban_guest_url');

        $this->assertSame(1, AssetStockToken::query()->where('asset_id', $asset->id)->whereNull('revoked_at')->count());
    }

    public function test_api_returns_422_when_rotating_an_active_kanban_tag(): void
    {
        $user = $this->manager();
        $asset = $this->stockAsset();

        $user->departments()->syncWithoutDetaching([$this->generalDepartment()->id]);
        Sanctum::actingAs($user, ['stock:write']);

        $this->postJson('/api/assets/'.$asset->id.'/stock-tokens', [
            'label' => 'kanban-b',
        ])->assertCreated();

        $this->postJson('/api/assets/'.$asset->id.'/stock-tokens', [
            'label' => 'kanban-b',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'label' => 'Revoke the active kanban-b tag before issuing a new one.',
            ]);

        $this->assertSame(1, AssetStockToken::query()->where('asset_id', $asset->id)->whereNull('revoked_at')->count());
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

    public function test_backdated_restock_adds_to_the_previous_event_not_later_on_hand(): void
    {
        $user = $this->scanner();
        $asset = $this->stockAsset(quantity: 10);
        $service = app(StockMovementService::class);

        $service->record($asset->fresh(), [
            'type' => StockMovementType::Count,
            'quantity' => 20,
            'recorded_at' => now()->subDays(10),
        ], $user);

        $service->record($asset->fresh(), [
            'type' => StockMovementType::Breakage,
            'quantity' => 5,
            'notes' => 'Later use',
            'recorded_at' => now()->subDay(),
        ], $user);

        $this->assertSame(15, (int) $asset->fresh()->quantity);

        $restock = $service->record($asset->fresh(), [
            'type' => StockMovementType::Restock,
            'quantity' => 10,
            'recorded_at' => now()->subDays(5),
        ], $user);

        $restock->refresh();
        $breakage = StockMovement::query()
            ->where('asset_id', $asset->id)
            ->where('type', StockMovementType::Breakage)
            ->first();

        $this->assertSame(20, (int) $restock->quantity_before);
        $this->assertSame(30, (int) $restock->quantity_after);
        $this->assertNotNull($breakage);
        $this->assertSame(30, (int) $breakage->quantity_before);
        $this->assertSame(25, (int) $breakage->quantity_after);
        $this->assertSame(25, (int) $asset->fresh()->quantity);
    }

    public function test_admin_can_update_and_delete_every_stock_event(): void
    {
        $admin = $this->admin();
        $asset = $this->stockAsset(quantity: 10);
        $service = app(StockMovementService::class);

        $count = $service->record($asset->fresh(), [
            'type' => StockMovementType::Count,
            'quantity' => 12,
            'recorded_at' => now()->subDays(4),
        ], $admin);

        $restock = $service->record($asset->fresh(), [
            'type' => StockMovementType::Restock,
            'quantity' => 3,
            'recorded_at' => now()->subDays(2),
        ], $admin);

        $breakage = $service->record($asset->fresh(), [
            'type' => StockMovementType::Breakage,
            'quantity' => 2,
            'notes' => 'Dropped',
            'recorded_at' => now()->subDay(),
        ], $admin);

        $this->actingAs($admin)
            ->patch(route('assets.stock-movements.update', [$asset, $restock]), [
                'movement_id' => $restock->id,
                'type' => 'restock',
                'quantity' => 8,
                'recorded_at' => $restock->recorded_at?->toDateString(),
                'notes' => 'Corrected delivery',
            ])
            ->assertRedirect(route('assets.show', [$asset, 'tab' => 'stock']));

        $this->assertSame(20, (int) $restock->fresh()->quantity_after);
        $this->assertSame(18, (int) $breakage->fresh()->quantity_after);
        $this->assertSame(18, (int) $asset->fresh()->quantity);

        $this->actingAs($admin)
            ->delete(route('assets.stock-movements.destroy', [$asset, $count]))
            ->assertRedirect(route('assets.show', [$asset, 'tab' => 'stock']));

        $this->assertDatabaseMissing('stock_movements', ['id' => $count->id]);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $asset->id,
            'action' => 'stock_movement_deleted',
        ]);
        $this->assertNotNull($restock->fresh());
    }

    public function test_scanner_cannot_update_or_delete_stock_events(): void
    {
        $scanner = $this->scanner();
        $asset = $this->stockAsset(quantity: 10);
        $movement = app(StockMovementService::class)->record($asset, [
            'type' => StockMovementType::Count,
            'quantity' => 9,
        ], $scanner);

        $this->actingAs($scanner)
            ->patch(route('assets.stock-movements.update', [$asset, $movement]), [
                'type' => 'count',
                'quantity' => 4,
                'recorded_at' => now()->toDateString(),
            ])
            ->assertForbidden();

        $this->actingAs($scanner)
            ->delete(route('assets.stock-movements.destroy', [$asset, $movement]))
            ->assertForbidden();

        $this->assertNotNull($movement->fresh());
        $this->assertSame(9, (int) $asset->fresh()->quantity);
    }

    public function test_guest_cannot_delete_a_stock_event(): void
    {
        $asset = $this->stockAsset(quantity: 10);
        $movement = StockMovement::factory()->create([
            'asset_id' => $asset->id,
            'quantity_before' => 10,
            'quantity_after' => 10,
            'quantity_delta' => 0,
        ]);

        $this->delete(route('assets.stock-movements.destroy', [$asset, $movement]))
            ->assertRedirect(route('login'));
    }

    public function test_stock_tracking_gate_helper_uses_developer_feature(): void
    {
        $user = User::factory()->create();
        $this->assertFalse(StockTracking::enabledFor($user));

        $this->withFeature($user);
        $this->assertTrue(StockTracking::enabledFor($user->fresh()));
    }
}
