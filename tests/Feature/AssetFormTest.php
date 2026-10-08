<?php

namespace Tests\Feature;

use App\Models\ItemType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_form_renders_searchable_selects(): void
    {
        $user = User::factory()->create();
        ItemType::create([
            'name' => 'LX Fixture',
            'slug' => 'lx-fixture',
        ]);

        $response = $this->actingAs($user)->get(route('assets.create'));

        $response->assertOk();
        $response->assertSee('searchableSelect(', false);
        $response->assertSee('placeholder="Search…"', false);
        $response->assertSee('name="item_type_id"', false);
        $response->assertSee('name="location_id"', false);
        $response->assertSee('name="parent_id"', false);
    }
}
