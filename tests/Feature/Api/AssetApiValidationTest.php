<?php

namespace Tests\Feature\Api;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\Department;
use App\Models\ItemType;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AssetApiValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    protected function actingScanner(): User
    {
        $user = User::factory()->create();
        $user->assignRole('scanner');

        $department = Department::query()->where('slug', 'general')->first()
            ?? Department::factory()->create(['slug' => 'general', 'name' => 'General']);
        $user->departments()->sync([$department->id]);

        Sanctum::actingAs($user);

        return $user;
    }

    public function test_store_rejects_invalid_status(): void
    {
        $this->actingScanner();
        $itemType = ItemType::factory()->create();

        $this->postJson('/api/assets', [
            'name' => 'Camera',
            'item_type_id' => $itemType->id,
            'status' => 'not-a-real-status',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);

        $this->assertDatabaseCount('assets', 0);
    }

    public function test_store_rejects_oversized_identifier_fields(): void
    {
        $this->actingScanner();
        $itemType = ItemType::factory()->create();

        $this->postJson('/api/assets', [
            'name' => 'Camera',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available->value,
            'fmi_ast' => str_repeat('A', 256),
            'ip_address' => str_repeat('1', 46),
            'mac_address' => str_repeat('B', 33),
            'serial_number' => str_repeat('C', 256),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['fmi_ast', 'ip_address', 'mac_address', 'serial_number']);

        $this->assertDatabaseCount('assets', 0);
    }

    public function test_store_accepts_valid_status_and_lengths(): void
    {
        $this->actingScanner();
        $itemType = ItemType::factory()->create();

        $response = $this->postJson('/api/assets', [
            'name' => 'Camera',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::InService->value,
            'fmi_ast' => 'FMI-1',
            'tp_barcode' => 'TP-1',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('assets', [
            'name' => 'Camera',
            'status' => AssetStatus::InService->value,
            'fmi_ast' => 'FMI-1',
        ]);
    }

    public function test_update_rejects_invalid_status(): void
    {
        $this->actingScanner();
        $asset = Asset::factory()->create();

        $this->putJson('/api/assets/'.$asset->id, [
            'status' => 'hacked',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);

        $this->assertSame(AssetStatus::Available, $asset->fresh()->status);
    }
}
