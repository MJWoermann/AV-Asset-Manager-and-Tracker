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

class CustomFieldUrlTypeTest extends TestCase
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

    public function test_manager_can_create_url_field_type(): void
    {
        $response = $this->actingAs($this->manager())->post(route('custom-field-sets.store'), [
            'name' => 'Docs Links',
            'fields' => [
                [
                    'name' => 'Manual URL',
                    'type' => 'url',
                    'is_required' => '0',
                ],
            ],
        ]);

        $fieldSet = CustomFieldSet::query()->where('name', 'Docs Links')->first();

        $this->assertNotNull($fieldSet);
        $this->assertCount(1, $fieldSet->definitions);
        $this->assertSame('url', $fieldSet->definitions[0]->type);
        $response->assertRedirect(route('custom-field-sets.index'));
    }

    public function test_asset_show_includes_open_url_when_value_is_valid(): void
    {
        $user = $this->manager();
        $itemType = ItemType::factory()->create();
        $fieldSet = CustomFieldSet::query()->create([
            'name' => 'Links',
            'slug' => 'links',
        ]);
        $itemType->fieldSets()->attach($fieldSet->id);

        $definition = CustomFieldDefinition::query()->create([
            'custom_field_set_id' => $fieldSet->id,
            'name' => 'Manual URL',
            'slug' => 'manual-url',
            'type' => 'url',
            'sort_order' => 0,
        ]);

        $asset = Asset::factory()->create([
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
        ]);

        AssetCustomFieldValue::query()->create([
            'asset_id' => $asset->id,
            'custom_field_definition_id' => $definition->id,
            'value' => 'https://example.com/manual',
        ]);

        $this->actingAs($user)
            ->get(route('assets.show', $asset))
            ->assertOk()
            ->assertSee('Open URL', false)
            ->assertSee('https://example.com/manual', false);
    }

    public function test_asset_show_omits_open_url_when_value_is_invalid(): void
    {
        $user = $this->manager();
        $itemType = ItemType::factory()->create();
        $fieldSet = CustomFieldSet::query()->create([
            'name' => 'Links',
            'slug' => 'links',
        ]);
        $itemType->fieldSets()->attach($fieldSet->id);

        $definition = CustomFieldDefinition::query()->create([
            'custom_field_set_id' => $fieldSet->id,
            'name' => 'Manual URL',
            'slug' => 'manual-url',
            'type' => 'url',
            'sort_order' => 0,
        ]);

        $asset = Asset::factory()->create([
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
        ]);

        AssetCustomFieldValue::query()->create([
            'asset_id' => $asset->id,
            'custom_field_definition_id' => $definition->id,
            'value' => 'javascript:alert(1)',
        ]);

        $response = $this->actingAs($user)
            ->get(route('assets.show', $asset))
            ->assertOk()
            ->assertSee('javascript:alert(1)', false);

        $this->assertSame(0, substr_count($response->getContent(), 'Open URL'));
    }

    public function test_openable_http_url_accepts_scheme_less_hosts(): void
    {
        $this->assertSame(
            'https://docs.example.com/path',
            CustomFieldDefinition::openableHttpUrl('docs.example.com/path')
        );
        $this->assertNull(CustomFieldDefinition::openableHttpUrl('not a url'));
        $this->assertNull(CustomFieldDefinition::openableHttpUrl('ftp://files.example.com/a'));
    }
}
