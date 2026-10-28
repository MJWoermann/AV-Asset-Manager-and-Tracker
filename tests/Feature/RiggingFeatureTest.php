<?php

namespace Tests\Feature;

use App\Enums\RigEventStatus;
use App\Models\ItemType;
use App\Models\RigEvent;
use App\Models\RiggingCalculationLog;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class RiggingFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    protected function withFeature(User $user): User
    {
        return $this->enableFeature($user, 'rigging');
    }

    protected function user(bool $feature = true, string $role = 'inventory_manager'): User
    {
        $user = User::factory()->create(['theme' => 'light']);
        $user->assignRole($role);

        return $feature ? $this->withFeature($user) : $user;
    }

    public function test_flag_off_returns_404_for_rigging_routes(): void
    {
        $user = $this->user(feature: false);

        $this->actingAs($user)->get(route('rigging.index'))->assertNotFound();
        $this->actingAs($user)->get(route('rigging.events.index'))->assertNotFound();
        $this->actingAs($user)->get(route('rigging.calculators.index'))->assertNotFound();
    }

    public function test_flag_on_allows_hub_events_and_calculators(): void
    {
        $user = $this->user();

        $this->actingAs($user)->get(route('rigging.index'))->assertOk();
        $this->actingAs($user)->get(route('rigging.events.index'))->assertOk();
        $this->actingAs($user)->get(route('rigging.calculators.index'))->assertOk();
        $this->actingAs($user)->get(route('rigging.calculators.show', 'two_point_hang'))->assertOk();
    }

    public function test_item_type_rigging_fields_only_when_feature_enabled(): void
    {
        $with = $this->user(feature: true);
        $without = $this->user(feature: false);

        $this->actingAs($with)->post(route('item-types.store'), [
            'name' => 'Truss Type A',
            'supports_rigging_calculator' => '1',
            'rigging_weight_kg' => '12.5',
        ])->assertRedirect(route('item-types.index'));

        $this->assertDatabaseHas('item_types', [
            'name' => 'Truss Type A',
            'supports_rigging_calculator' => 1,
            'rigging_weight_kg' => 12.5,
        ]);

        $this->actingAs($without)->post(route('item-types.store'), [
            'name' => 'Truss Type B',
            'supports_rigging_calculator' => '1',
            'rigging_weight_kg' => '99',
        ])->assertRedirect(route('item-types.index'));

        $typeB = ItemType::query()->where('name', 'Truss Type B')->first();
        $this->assertFalse((bool) $typeB->supports_rigging_calculator);
        $this->assertNull($typeB->rigging_weight_kg);
    }

    public function test_calc_log_store_is_server_computed_and_immutable(): void
    {
        $user = $this->user();

        $this->actingAs($user)->post(route('rigging.calculators.logs.store', 'two_point_hang'), [
            'title' => 'Hang A',
            'inputs' => json_encode(['W' => 100, 'L' => 10, 'x' => 4]),
        ])->assertRedirect();

        $log = RiggingCalculationLog::query()->first();
        $this->assertNotNull($log);
        $this->assertEqualsWithDelta(60.0, $log->results['RA'], 0.001);
        $this->assertSame($user->id, $log->created_by);

        $this->expectException(RuntimeException::class);
        $log->update(['title' => 'Hacked']);
    }

    public function test_calc_log_attach_detach_and_single_event(): void
    {
        $user = $this->user();
        $eventA = RigEvent::factory()->create(['created_by' => $user->id, 'name' => 'Event A']);
        $eventB = RigEvent::factory()->create(['created_by' => $user->id, 'name' => 'Event B']);

        $log = RiggingCalculationLog::factory()->create([
            'created_by' => $user->id,
            'rig_event_id' => null,
        ]);

        $this->actingAs($user)->post(route('rigging.events.calculations.attach', $eventA), [
            'rigging_calculation_log_id' => $log->id,
        ])->assertRedirect();

        $this->assertSame($eventA->id, $log->fresh()->rig_event_id);

        $this->actingAs($user)->post(route('rigging.events.calculations.attach', $eventB), [
            'rigging_calculation_log_id' => $log->id,
        ])->assertSessionHasErrors('rigging_calculation_log_id');

        $this->assertSame($eventA->id, $log->fresh()->rig_event_id);

        $this->actingAs($user)->delete(route('rigging.events.calculations.detach', [$eventA, $log]))
            ->assertRedirect();

        $this->assertNull($log->fresh()->rig_event_id);
        $this->assertDatabaseHas('rigging_calculation_logs', ['id' => $log->id]);
    }

    public function test_save_log_with_rig_event_id(): void
    {
        $user = $this->user();
        $event = RigEvent::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user)->post(route('rigging.calculators.logs.store', 'dead_hang'), [
            'title' => 'Dead hang',
            'rig_event_id' => $event->id,
            'inputs' => json_encode(['W' => 50, 'wll' => 100]),
        ])->assertRedirect();

        $this->assertDatabaseHas('rigging_calculation_logs', [
            'calculator' => 'dead_hang',
            'rig_event_id' => $event->id,
        ]);
    }

    public function test_rig_event_crud_checklist_motor_patches_and_sign_off(): void
    {
        $user = $this->user();

        $this->actingAs($user)->post(route('rigging.events.store'), [
            'name' => 'Gala Rig',
            'venue_space' => 'Auditorium',
            'event_date' => '2026-11-01',
            'status' => RigEventStatus::InProgress->value,
            'engineer_required' => '1',
            'engineer_acquired' => '0',
        ])->assertRedirect();

        $event = RigEvent::query()->where('name', 'Gala Rig')->first();
        $this->assertNotNull($event);
        $this->assertTrue($event->engineer_required);

        $this->actingAs($user)->put(route('rigging.events.checklist', $event), [
            'process_checklist' => [
                'contractor_docs' => '1',
                'avpm_review' => '0',
            ],
        ])->assertRedirect();

        $event->refresh();
        $this->assertTrue($event->process_checklist['contractor_docs']);
        $this->assertFalse($event->process_checklist['avpm_review']);

        $this->actingAs($user)->put(route('rigging.events.motor-patches.sync', $event), [
            'motor_patches' => [
                ['motor_label' => 'M1', 'patch_to' => 'Ch 1', 'notes' => 'DS'],
                ['motor_label' => 'M2', 'patch_to' => 'Ch 2'],
            ],
        ])->assertRedirect();

        $this->assertCount(2, $event->fresh()->motorPatches);

        $this->actingAs($user)->post(route('rigging.events.sign-off', $event), [
            'structure_signed_name' => 'Alex Rigger',
            'structure_notes' => 'All good',
        ])->assertRedirect();

        $event->refresh();
        $this->assertTrue($event->isSignedOff());
        $this->assertSame(RigEventStatus::SignedOff, $event->status);
        $this->assertSame('Alex Rigger', $event->structure_signed_name);
    }

    public function test_udl_compute_endpoint_uses_item_type_weight(): void
    {
        $user = $this->user();
        $type = ItemType::factory()->riggingCatalogue(40)->create();

        $response = $this->actingAs($user)->postJson(route('rigging.calculators.compute', 'udl_multi_point'), [
            'inputs' => [
                'point_count' => 2,
                'distribution_mode' => 'equal',
                'weight_lines' => [
                    ['item_type_id' => $type->id, 'quantity' => 1],
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('results.total_load_kg', 40);
    }

    public function test_scanner_can_access_rigging_when_feature_enabled(): void
    {
        $scanner = $this->user(feature: true, role: 'scanner');

        $this->actingAs($scanner)->get(route('rigging.events.create'))->assertOk();
    }
}
