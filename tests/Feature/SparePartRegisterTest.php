<?php

namespace Tests\Feature;

use App\Enums\SparePartMovementType;
use App\Models\Asset;
use App\Models\SpareGroup;
use App\Models\SparePart;
use App\Models\User;
use App\Services\SparePartChartService;
use App\Services\SparePartService;
use App\Support\SparePartLuhn;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SparePartRegisterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    protected function manager(): User
    {
        $user = User::factory()->create();
        $user->assignRole('inventory_manager');

        return $user;
    }

    protected function scanner(): User
    {
        $user = User::factory()->create();
        $user->assignRole('scanner');

        return $user;
    }

    public function test_scanner_can_view_spares_but_not_create(): void
    {
        $spare = SparePart::factory()->create();

        $this->actingAs($this->scanner())
            ->get(route('spares.index'))
            ->assertOk()
            ->assertSee($spare->code);

        $this->actingAs($this->scanner())
            ->get(route('spares.create'))
            ->assertForbidden();
    }

    public function test_manager_can_create_spare_with_luhn_code(): void
    {
        $code = SparePartLuhn::generate();
        $this->assertTrue(SparePartLuhn::isValid($code));

        $response = $this->actingAs($this->manager())->post(route('spares.store'), [
            'code' => $code,
            'kind' => 'spare_part',
            'component' => 'Latch kit',
            'quantity_on_hand' => 3,
            'min_holding' => 1,
            'max_holding' => 5,
        ]);

        $spare = SparePart::query()->where('code', $code)->first();
        $this->assertNotNull($spare);
        $response->assertRedirect(route('spares.show', $spare));
        $this->assertSame(3, $spare->quantity_on_hand);
        $this->assertTrue($spare->movements()->exists());
    }

    public function test_use_movement_requires_asset_and_reason_and_decrements_stock(): void
    {
        $manager = $this->manager();
        $spare = SparePart::factory()->create(['quantity_on_hand' => 5]);
        $asset = Asset::factory()->create();

        $this->actingAs($manager)->post(route('spares.movements.store', $spare), [
            'type' => SparePartMovementType::Use->value,
            'quantity' => 2,
        ])->assertSessionHasErrors(['against_asset_id', 'reason']);

        $this->actingAs($manager)->post(route('spares.movements.store', $spare), [
            'type' => SparePartMovementType::Use->value,
            'quantity' => 2,
            'against_asset_id' => $asset->id,
            'reason' => 'Broken latch',
        ])->assertRedirect();

        $spare->refresh();
        $this->assertSame(3, $spare->quantity_on_hand);
    }

    public function test_cannot_oversell_on_hand_quantity(): void
    {
        $spare = SparePart::factory()->create(['quantity_on_hand' => 1]);
        $asset = Asset::factory()->create();

        $this->actingAs($this->manager())->post(route('spares.movements.store', $spare), [
            'type' => SparePartMovementType::Use->value,
            'quantity' => 5,
            'against_asset_id' => $asset->id,
            'reason' => 'Test',
        ])->assertSessionHasErrors('quantity');

        $this->assertSame(1, $spare->fresh()->quantity_on_hand);
    }

    public function test_supersession_keeps_distinct_rows_and_links_successor(): void
    {
        $old = SparePart::factory()->create();
        $new = SparePart::factory()->create();

        app(SparePartService::class)->supersede($old, $new, $this->manager());

        $old->refresh();
        $this->assertSame($new->id, $old->superseded_by_id);
        $this->assertNotSame($old->id, $new->id);

        $this->actingAs($this->manager())
            ->get(route('spares.show', $old))
            ->assertOk()
            ->assertSee($new->code);
    }

    public function test_supersession_rejects_self_reference(): void
    {
        $spare = SparePart::factory()->create();

        $this->actingAs($this->manager())->post(route('spares.supersede', $spare), [
            'superseded_by_id' => $spare->id,
        ])->assertSessionHasErrors('superseded_by_id');
    }

    public function test_spare_group_stores_member_quantities(): void
    {
        $a = SparePart::factory()->create();
        $b = SparePart::factory()->create();

        $this->actingAs($this->manager())->post(route('spares.groups.store'), [
            'name' => 'LED kit',
            'items' => [
                ['spare_part_id' => $a->id, 'quantity' => 2],
                ['spare_part_id' => $b->id, 'quantity' => 1],
            ],
        ])->assertRedirect();

        $group = SpareGroup::query()->where('name', 'LED kit')->first();
        $this->assertNotNull($group);
        $this->assertCount(2, $group->items);
        $this->assertSame(2, $group->items->firstWhere('spare_part_id', $a->id)?->quantity);
    }

    public function test_chart_service_returns_points_after_movement(): void
    {
        $spare = SparePart::factory()->create(['quantity_on_hand' => 0, 'min_holding' => 1]);
        $service = app(SparePartService::class);
        $service->record($spare, [
            'type' => SparePartMovementType::Restock,
            'quantity' => 4,
        ], $this->manager());

        $chart = app(SparePartChartService::class)->build($spare->fresh());
        $this->assertSame(4, $chart['current_quantity']);
        $this->assertNotEmpty($chart['points']);
    }

    public function test_asset_spares_tab_lists_movements(): void
    {
        $asset = Asset::factory()->create();
        $spare = SparePart::factory()->create(['quantity_on_hand' => 2]);

        app(SparePartService::class)->record($spare, [
            'type' => SparePartMovementType::Replace->value,
            'quantity' => 1,
            'against_asset_id' => $asset->id,
            'reason' => 'Hot swap',
        ], $this->manager());

        $this->actingAs($this->scanner())
            ->get(route('assets.show', [$asset, 'tab' => 'spares']))
            ->assertOk()
            ->assertSee($spare->code)
            ->assertSee('Hot swap');
    }

    public function test_lookup_finds_spare_by_luhn_code(): void
    {
        $spare = SparePart::factory()->create();

        $this->actingAs($this->scanner())
            ->getJson(route('spares.lookup', ['code' => $spare->code]))
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('code', $spare->code);
    }

    public function test_luhn_helper_rejects_invalid_codes(): void
    {
        $this->assertFalse(SparePartLuhn::isValid('123'));
    }
}
