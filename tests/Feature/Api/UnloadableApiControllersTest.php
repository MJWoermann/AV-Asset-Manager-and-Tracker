<?php

namespace Tests\Feature\Api;

use App\Http\Controllers\Api\ConnectionTypeApiController;
use App\Http\Controllers\Api\CustomFieldSetApiController;
use App\Http\Controllers\Api\FileApiController;
use App\Http\Controllers\Api\RepairProcedureApiController;
use App\Http\Controllers\ConnectionTypeController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\CustomFieldSetController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\RepairProcedureController;
use App\Models\File;
use App\Models\RepairProcedure;
use App\Models\User;
use App\Support\ConnectionTypeIcons;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use ReflectionClass;
use Tests\TestCase;

class UnloadableApiControllersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    /**
     * @return list<class-string>
     */
    protected function previouslyUnloadableControllers(): array
    {
        return [
            FileApiController::class,
            ConnectionTypeApiController::class,
            CustomFieldSetApiController::class,
            RepairProcedureApiController::class,
        ];
    }

    public function test_api_controllers_load_via_reflection(): void
    {
        foreach ($this->previouslyUnloadableControllers() as $class) {
            $reflection = new ReflectionClass($class);
            $this->assertTrue($reflection->isInstantiable(), $class.' should be instantiable');
            $this->assertTrue(
                is_subclass_of($class, Controller::class),
                $class.' should extend the base Controller',
            );
            $this->assertFalse(
                is_subclass_of($class, FileController::class)
                || is_subclass_of($class, ConnectionTypeController::class)
                || is_subclass_of($class, CustomFieldSetController::class)
                || is_subclass_of($class, RepairProcedureController::class),
                $class.' must not extend a web controller with incompatible signatures',
            );
        }
    }

    protected function managerWithAbilities(array $abilities, array $features = []): User
    {
        $user = User::factory()->create();
        $user->assignRole('inventory_manager');
        $user->departments()->sync([$this->generalDepartment()->id]);

        if ($features !== []) {
            $user->preferences = array_replace_recursive($user->preferences ?? [], [
                'features' => $features,
            ]);
            $user->save();
        }

        Sanctum::actingAs($user, $abilities);

        return $user;
    }

    public function test_files_index_returns_json(): void
    {
        $this->managerWithAbilities(['files:read']);
        File::factory()->create(['title' => 'API Manual']);

        $this->getJson('/api/files')
            ->assertOk()
            ->assertJsonFragment(['title' => 'API Manual']);
    }

    public function test_connection_types_crud_via_api(): void
    {
        $this->managerWithAbilities(
            ['connections:read', 'connections:write'],
            ['asset_connections' => true],
        );

        $icon = ConnectionTypeIcons::keys()[0];

        $create = $this->postJson('/api/connection-types', [
            'name' => 'API Fibre',
            'color' => '#00adb7',
            'icon' => $icon,
            'allows_protocol' => false,
            'allows_bus' => true,
            'sort_order' => 50,
        ]);

        $create->assertCreated()->assertJsonPath('name', 'API Fibre');
        $id = $create->json('id');

        $this->getJson('/api/connection-types')
            ->assertOk()
            ->assertJsonFragment(['name' => 'API Fibre']);

        $this->putJson("/api/connection-types/{$id}", [
            'name' => 'API Fibre Renamed',
            'color' => '#112233',
            'icon' => $icon,
            'allows_protocol' => true,
            'allows_bus' => true,
            'sort_order' => 50,
        ])->assertOk()->assertJsonPath('name', 'API Fibre Renamed');

        $this->deleteJson("/api/connection-types/{$id}")
            ->assertOk()
            ->assertJsonPath('message', 'Connection type deleted.');

        $this->assertSoftDeleted('connection_types', ['id' => $id]);
    }

    public function test_custom_field_sets_crud_via_api(): void
    {
        $this->managerWithAbilities(['catalog:read', 'catalog:write']);

        $create = $this->postJson('/api/custom-field-sets', [
            'name' => 'API Network',
            'description' => 'From API',
            'fields' => [
                ['name' => 'IP Address', 'type' => 'text', 'is_required' => true],
            ],
        ]);

        $create->assertCreated()->assertJsonPath('name', 'API Network');
        $id = $create->json('id');
        $this->assertCount(1, $create->json('definitions'));

        $this->getJson('/api/custom-field-sets')
            ->assertOk()
            ->assertJsonFragment(['name' => 'API Network']);

        $this->getJson("/api/custom-field-sets/{$id}")
            ->assertOk()
            ->assertJsonPath('name', 'API Network');

        $this->putJson("/api/custom-field-sets/{$id}", [
            'name' => 'API Network Renamed',
            'fields' => [
                ['name' => 'MAC', 'type' => 'text', 'is_required' => false],
            ],
        ])->assertOk()->assertJsonPath('name', 'API Network Renamed');

        $this->deleteJson("/api/custom-field-sets/{$id}")
            ->assertOk();

        $this->assertDatabaseMissing('custom_field_sets', ['id' => $id]);
    }

    public function test_repair_procedures_index_and_show_via_api(): void
    {
        $this->managerWithAbilities(['repairs:read', 'repairs:write']);

        $procedure = RepairProcedure::factory()->create(['title' => 'Swap PSU']);

        $this->getJson('/api/repair-procedures')
            ->assertOk()
            ->assertJsonFragment(['title' => 'Swap PSU']);

        $this->getJson('/api/repair-procedures/'.$procedure->id)
            ->assertOk()
            ->assertJsonPath('title', 'Swap PSU');

        $this->deleteJson('/api/repair-procedures/'.$procedure->id)
            ->assertOk()
            ->assertJsonPath('message', 'Repair procedure deleted.');

        $this->assertDatabaseMissing('repair_procedures', ['id' => $procedure->id]);
    }
}
