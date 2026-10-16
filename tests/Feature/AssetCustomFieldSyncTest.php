<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetCustomFieldValue;
use App\Models\CustomFieldDefinition;
use App\Models\CustomFieldSet;
use App\Models\Department;
use App\Models\ItemType;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetCustomFieldSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function manager(): User
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('inventory_manager');

        $department = Department::query()->where('slug', 'general')->first()
            ?? Department::factory()->create(['slug' => 'general', 'name' => 'General']);
        $user->departments()->sync([$department->id]);

        return $user;
    }

    public function test_store_ignores_custom_field_definitions_not_on_item_type(): void
    {
        $user = $this->manager();
        $itemType = ItemType::factory()->create();

        $attachedSet = CustomFieldSet::query()->create([
            'name' => 'Attached',
            'slug' => 'attached',
        ]);
        $foreignSet = CustomFieldSet::query()->create([
            'name' => 'Foreign',
            'slug' => 'foreign',
        ]);
        $itemType->fieldSets()->attach($attachedSet->id);

        $allowed = CustomFieldDefinition::query()->create([
            'custom_field_set_id' => $attachedSet->id,
            'name' => 'VLAN',
            'slug' => 'vlan',
            'type' => 'text',
            'sort_order' => 1,
        ]);
        $foreign = CustomFieldDefinition::query()->create([
            'custom_field_set_id' => $foreignSet->id,
            'name' => 'Secret',
            'slug' => 'secret',
            'type' => 'text',
            'sort_order' => 1,
        ]);

        $this->actingAs($user)->post(route('assets.store'), [
            'name' => 'Scoped Asset',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available->value,
            'custom_fields' => [
                $allowed->id => '100',
                $foreign->id => 'should-not-store',
            ],
        ])->assertRedirect();

        $asset = Asset::query()->where('name', 'Scoped Asset')->first();
        $this->assertNotNull($asset);

        $this->assertDatabaseHas('asset_custom_field_values', [
            'asset_id' => $asset->id,
            'custom_field_definition_id' => $allowed->id,
            'value' => '100',
        ]);
        $this->assertDatabaseMissing('asset_custom_field_values', [
            'asset_id' => $asset->id,
            'custom_field_definition_id' => $foreign->id,
        ]);
    }

    public function test_update_ignores_unknown_definition_ids(): void
    {
        $user = $this->manager();
        $itemType = ItemType::factory()->create();
        $asset = Asset::factory()->create([
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
        ]);

        $this->actingAs($user)->put(route('assets.update', $asset), [
            'name' => $asset->name,
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available->value,
            'custom_fields' => [
                999999 => 'ghost',
            ],
        ])->assertRedirect();

        $this->assertSame(0, AssetCustomFieldValue::query()->where('asset_id', $asset->id)->count());
    }
}
