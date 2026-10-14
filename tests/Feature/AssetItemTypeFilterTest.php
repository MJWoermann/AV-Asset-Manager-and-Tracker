<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\ItemType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetItemTypeFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_assets_index_filters_by_item_type(): void
    {
        $user = User::factory()->create();
        $fixture = ItemType::create([
            'name' => 'LX Fixture',
            'slug' => 'lx-fixture',
        ]);
        $rack = ItemType::create([
            'name' => 'Rack/RoadCase',
            'slug' => 'rack-roadcase',
        ]);

        Asset::create([
            'name' => 'Fixture Asset',
            'item_type_id' => $fixture->id,
            'status' => AssetStatus::Available,
        ]);
        Asset::create([
            'name' => 'Rack Asset',
            'item_type_id' => $rack->id,
            'status' => AssetStatus::Available,
        ]);

        $response = $this->actingAs($user)->get(route('assets.index', [
            'item_type_id' => $fixture->id,
        ]));

        $response->assertOk();
        $response->assertSee('Fixture Asset');
        $response->assertDontSee('Rack Asset');
        $response->assertSee('searchableSelect(', false);
        $response->assertSee('name="item_type_id"', false);
        $response->assertSee('id="filter_item_type_id"', false);
        $response->assertSee('All item types');
        $response->assertSee($fixture->name);
    }

    public function test_assets_index_item_type_filter_combines_with_status(): void
    {
        $user = User::factory()->create();
        $fixture = ItemType::create([
            'name' => 'LX Fixture',
            'slug' => 'lx-fixture',
        ]);

        Asset::create([
            'name' => 'Available Fixture',
            'item_type_id' => $fixture->id,
            'status' => AssetStatus::Available,
        ]);
        Asset::create([
            'name' => 'Lost Fixture',
            'item_type_id' => $fixture->id,
            'status' => AssetStatus::Lost,
        ]);

        $response = $this->actingAs($user)->get(route('assets.index', [
            'item_type_id' => $fixture->id,
            'status' => AssetStatus::Available->value,
        ]));

        $response->assertOk();
        $response->assertSee('Available Fixture');
        $response->assertDontSee('Lost Fixture');
    }
}
