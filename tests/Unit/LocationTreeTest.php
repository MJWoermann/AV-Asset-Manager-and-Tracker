<?php

namespace Tests\Unit;

use App\Enums\LocationType;
use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationTreeTest extends TestCase
{
    use RefreshDatabase;

    public function test_tree_nests_children_under_parent_nodes(): void
    {
        $site = Location::create([
            'name' => 'Main Warehouse',
            'type' => LocationType::Site,
        ]);
        $level = Location::create([
            'name' => 'Ground Floor',
            'type' => LocationType::Level,
            'parent_id' => $site->id,
        ]);
        Location::create([
            'name' => 'Portable Pool',
            'type' => LocationType::Site,
            'is_portable' => true,
        ]);

        $tree = Location::tree();

        $this->assertCount(2, $tree);
        $this->assertSame('Main Warehouse', $tree->first()->name);
        $this->assertCount(1, $tree->first()->children);
        $this->assertSame($level->id, $tree->first()->children->first()->id);
        $this->assertSame('Portable Pool', $tree->last()->name);
        $this->assertCount(0, $tree->last()->children);
    }

    public function test_self_and_descendant_ids_includes_nested_locations(): void
    {
        $site = Location::create([
            'name' => 'Main Warehouse',
            'type' => LocationType::Site,
        ]);
        $level = Location::create([
            'name' => 'Ground Floor',
            'type' => LocationType::Level,
            'parent_id' => $site->id,
        ]);
        $room = Location::create([
            'name' => 'Store A',
            'type' => LocationType::Room,
            'parent_id' => $level->id,
        ]);
        Location::create([
            'name' => 'Portable Pool',
            'type' => LocationType::Site,
            'is_portable' => true,
        ]);

        $ids = Location::selfAndDescendantIds($site->id);
        sort($ids);

        $expected = [$site->id, $level->id, $room->id];
        sort($expected);

        $this->assertSame($expected, $ids);
    }
}
