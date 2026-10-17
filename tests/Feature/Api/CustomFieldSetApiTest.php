<?php

namespace Tests\Feature\Api;

use App\Models\CustomFieldSet;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomFieldSetApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    protected function actingManager(): User
    {
        $user = User::factory()->create();
        $user->assignRole('inventory_manager');
        Sanctum::actingAs($user, ['catalog:read', 'catalog:write']);

        return $user;
    }

    protected function actingScanner(): User
    {
        $user = User::factory()->create();
        $user->assignRole('scanner');
        Sanctum::actingAs($user, ['catalog:read', 'catalog:write']);

        return $user;
    }

    public function test_store_creates_field_set_and_returns_201(): void
    {
        $this->actingManager();

        $response = $this->postJson('/api/custom-field-sets', [
            'name' => 'Network Details',
            'description' => 'IP and MAC fields',
            'fields' => [
                [
                    'name' => 'IP Address',
                    'type' => 'text',
                    'is_required' => true,
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('name', 'Network Details')
            ->assertJsonPath('slug', 'network-details')
            ->assertJsonPath('definitions.0.name', 'IP Address');

        $this->assertDatabaseHas('custom_field_sets', [
            'name' => 'Network Details',
            'slug' => 'network-details',
        ]);
    }

    public function test_update_renames_field_set_and_returns_json(): void
    {
        $this->actingManager();

        $fieldSet = CustomFieldSet::query()->create([
            'name' => 'Old Name',
            'slug' => 'old-name',
        ]);

        $response = $this->putJson('/api/custom-field-sets/'.$fieldSet->id, [
            'name' => 'New Name',
            'description' => 'Updated',
            'fields' => [],
        ]);

        $response->assertOk()
            ->assertJsonPath('name', 'New Name')
            ->assertJsonPath('slug', 'new-name');

        $this->assertDatabaseHas('custom_field_sets', [
            'id' => $fieldSet->id,
            'name' => 'New Name',
            'slug' => 'new-name',
        ]);
    }

    public function test_destroy_deletes_field_set_and_returns_json_message(): void
    {
        $this->actingManager();

        $fieldSet = CustomFieldSet::query()->create([
            'name' => 'Disposable',
            'slug' => 'disposable',
        ]);

        $response = $this->deleteJson('/api/custom-field-sets/'.$fieldSet->id);

        $response->assertOk()
            ->assertJsonPath('message', 'Custom field set deleted.');

        $this->assertDatabaseMissing('custom_field_sets', [
            'id' => $fieldSet->id,
        ]);
    }

    public function test_destroy_returns_403_for_scanner(): void
    {
        $this->actingScanner();

        $fieldSet = CustomFieldSet::query()->create([
            'name' => 'Protected',
            'slug' => 'protected',
        ]);

        $this->deleteJson('/api/custom-field-sets/'.$fieldSet->id)
            ->assertForbidden();

        $this->assertDatabaseHas('custom_field_sets', [
            'id' => $fieldSet->id,
        ]);
    }

    public function test_store_returns_403_for_scanner(): void
    {
        $this->actingScanner();

        $this->postJson('/api/custom-field-sets', [
            'name' => 'Should Fail',
            'fields' => [],
        ])->assertForbidden();

        $this->assertDatabaseMissing('custom_field_sets', [
            'name' => 'Should Fail',
        ]);
    }
}
