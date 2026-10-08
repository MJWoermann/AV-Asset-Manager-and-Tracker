<?php

namespace Tests\Feature;

use App\Models\CustomFieldDefinition;
use App\Models\CustomFieldSet;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomFieldSetTest extends TestCase
{
    use RefreshDatabase;

    protected function manager(): User
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('inventory_manager');

        return $user;
    }

    protected function scanner(): User
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('scanner');

        return $user;
    }

    public function test_scanner_is_forbidden_from_field_set_index(): void
    {
        $this->actingAs($this->scanner())
            ->get(route('custom-field-sets.index'))
            ->assertForbidden();
    }

    public function test_manager_can_create_field_set_with_fields(): void
    {
        $response = $this->actingAs($this->manager())->post(route('custom-field-sets.store'), [
            'name' => 'Network Details',
            'description' => 'IP and MAC fields',
            'fields' => [
                [
                    'name' => 'IP Address',
                    'type' => 'text',
                    'is_required' => '1',
                ],
                [
                    'name' => 'Portable',
                    'type' => 'checkbox',
                    'is_required' => '0',
                ],
            ],
        ]);

        $fieldSet = CustomFieldSet::query()->where('name', 'Network Details')->first();

        $this->assertNotNull($fieldSet);
        $this->assertSame('network-details', $fieldSet->slug);
        $this->assertSame('IP and MAC fields', $fieldSet->description);
        $this->assertCount(2, $fieldSet->definitions);
        $this->assertSame('IP Address', $fieldSet->definitions[0]->name);
        $this->assertTrue($fieldSet->definitions[0]->is_required);
        $this->assertSame('ip-address', $fieldSet->definitions[0]->slug);
        $response->assertRedirect(route('custom-field-sets.index'));
    }

    public function test_manager_can_rename_field_and_add_new_field(): void
    {
        $fieldSet = CustomFieldSet::create([
            'name' => 'Network Details',
            'slug' => 'network-details',
        ]);
        $definition = CustomFieldDefinition::create([
            'custom_field_set_id' => $fieldSet->id,
            'name' => 'IP Address',
            'slug' => 'ip-address',
            'type' => 'text',
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($this->manager())->put(route('custom-field-sets.update', $fieldSet), [
            'name' => 'Network Details',
            'fields' => [
                [
                    'id' => $definition->id,
                    'name' => 'Host IP',
                    'type' => 'text',
                    'is_required' => '1',
                ],
                [
                    'name' => 'MAC Address',
                    'type' => 'text',
                    'is_required' => '0',
                ],
            ],
        ]);

        $response->assertRedirect(route('custom-field-sets.index'));

        $definition->refresh();
        $this->assertSame('Host IP', $definition->name);
        $this->assertSame('ip-address', $definition->slug);
        $this->assertTrue($definition->is_required);
        $this->assertDatabaseHas('custom_field_definitions', [
            'custom_field_set_id' => $fieldSet->id,
            'name' => 'MAC Address',
            'slug' => 'mac-address',
        ]);
        $this->assertCount(2, $fieldSet->fresh()->definitions);
    }

    public function test_manager_can_remove_field_from_set(): void
    {
        $fieldSet = CustomFieldSet::create([
            'name' => 'Network Details',
            'slug' => 'network-details',
        ]);
        $keep = CustomFieldDefinition::create([
            'custom_field_set_id' => $fieldSet->id,
            'name' => 'IP Address',
            'slug' => 'ip-address',
            'type' => 'text',
            'sort_order' => 0,
        ]);
        $remove = CustomFieldDefinition::create([
            'custom_field_set_id' => $fieldSet->id,
            'name' => 'Old Field',
            'slug' => 'old-field',
            'type' => 'text',
            'sort_order' => 1,
        ]);

        $this->actingAs($this->manager())->put(route('custom-field-sets.update', $fieldSet), [
            'name' => 'Network Details',
            'fields' => [
                [
                    'id' => $keep->id,
                    'name' => 'IP Address',
                    'type' => 'text',
                    'is_required' => '0',
                ],
            ],
        ])->assertRedirect(route('custom-field-sets.index'));

        $this->assertDatabaseHas('custom_field_definitions', ['id' => $keep->id]);
        $this->assertDatabaseMissing('custom_field_definitions', ['id' => $remove->id]);
    }

    public function test_select_field_stores_parsed_options(): void
    {
        $this->actingAs($this->manager())->post(route('custom-field-sets.store'), [
            'name' => 'Connector Set',
            'fields' => [
                [
                    'name' => 'Connector',
                    'type' => 'select',
                    'options' => "XLR, etherCON\nPowerCON",
                    'is_required' => '0',
                ],
            ],
        ])->assertRedirect(route('custom-field-sets.index'));

        $definition = CustomFieldDefinition::query()->where('name', 'Connector')->first();

        $this->assertNotNull($definition);
        $this->assertSame(['XLR', 'etherCON', 'PowerCON'], $definition->options);
    }

    public function test_create_requires_set_name(): void
    {
        $this->actingAs($this->manager())
            ->post(route('custom-field-sets.store'), [
                'name' => '',
                'fields' => [
                    ['name' => 'IP Address', 'type' => 'text'],
                ],
            ])
            ->assertSessionHasErrors(['name']);
    }

    public function test_edit_form_renders_existing_fields(): void
    {
        $fieldSet = CustomFieldSet::create([
            'name' => 'Network Details',
            'slug' => 'network-details',
        ]);
        CustomFieldDefinition::create([
            'custom_field_set_id' => $fieldSet->id,
            'name' => 'IP Address',
            'slug' => 'ip-address',
            'type' => 'text',
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($this->manager())->get(route('custom-field-sets.edit', $fieldSet));

        $response->assertOk();
        $response->assertSee('Network Details');
        $response->assertSee('IP Address');
        $response->assertSee('customFieldSetForm(', false);
    }
}
