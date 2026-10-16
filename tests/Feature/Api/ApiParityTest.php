<?php

namespace Tests\Feature\Api;

use App\Enums\LocationType;
use App\Models\AssetList;
use App\Models\Department;
use App\Models\Location;
use App\Models\ScanSession;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiParityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    protected function scannerWithAbilities(array $abilities): User
    {
        $user = User::factory()->create();
        $user->assignRole('scanner');

        $department = Department::query()->where('slug', 'general')->first()
            ?? Department::factory()->create(['slug' => 'general', 'name' => 'General']);
        $user->departments()->sync([$department->id]);

        Sanctum::actingAs($user, $abilities);

        return $user;
    }

    public function test_location_index_requires_locations_read_ability(): void
    {
        $this->scannerWithAbilities(['user:read']);

        $this->getJson('/api/locations')
            ->assertForbidden();
    }

    public function test_location_index_returns_json_with_ability(): void
    {
        $this->scannerWithAbilities(['locations:read']);
        Location::create(['name' => 'Site A', 'type' => LocationType::Site]);

        $this->getJson('/api/locations')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_scan_session_start_requires_scan_sessions_ability(): void
    {
        $user = $this->scannerWithAbilities(['scan']);
        $eventList = AssetList::create([
            'name' => 'Event',
            'type' => 'event',
            'created_by' => $user->id,
            'department_id' => $this->generalDepartment()->id,
        ]);

        $this->postJson('/api/scan-sessions', [
            'event_list_id' => $eventList->id,
        ])->assertForbidden();
    }

    public function test_scan_session_can_be_started_and_closed(): void
    {
        $user = $this->scannerWithAbilities(['scan-sessions', 'scan']);
        $eventList = AssetList::create([
            'name' => 'Event',
            'type' => 'event',
            'created_by' => $user->id,
            'department_id' => $this->generalDepartment()->id,
        ]);

        $start = $this->postJson('/api/scan-sessions', [
            'event_list_id' => $eventList->id,
        ]);

        $start->assertCreated();
        $sessionId = $start->json('id');
        $this->assertNotNull($sessionId);

        $this->postJson("/api/scan-sessions/{$sessionId}/close")
            ->assertOk()
            ->assertJsonPath('status', 'closed');

        $this->assertSame('closed', ScanSession::find($sessionId)?->status);
    }

    public function test_active_department_patch_persists_in_preferences(): void
    {
        $user = $this->scannerWithAbilities(['user:read']);
        $department = $this->generalDepartment();

        $this->patchJson('/api/me/active-department', [
            'department_id' => $department->id,
        ])
            ->assertOk()
            ->assertJsonPath('department_id', $department->id);

        $this->assertSame(
            $department->id,
            $user->fresh()->preferences['active_department_id'] ?? null,
        );
    }

    public function test_inventory_manager_can_create_list_via_api(): void
    {
        $user = User::factory()->create();
        $user->assignRole('inventory_manager');
        $user->departments()->sync([$this->generalDepartment()->id]);

        Sanctum::actingAs($user, ['lists:write']);

        $this->postJson('/api/lists', [
            'name' => 'API Event',
            'type' => 'event',
        ])
            ->assertCreated()
            ->assertJsonPath('name', 'API Event');
    }
}
