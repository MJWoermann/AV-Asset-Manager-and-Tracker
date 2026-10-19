<?php

namespace Database\Seeders;

use App\Enums\LocationType;
use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $site = Location::firstOrCreate(
            ['name' => 'Main Warehouse', 'type' => LocationType::Site],
            ['is_portable' => false]
        );

        $building = Location::firstOrCreate(
            ['name' => 'Building A', 'type' => LocationType::Building, 'parent_id' => $site->id]
        );

        $level = Location::firstOrCreate(
            ['name' => 'Ground Floor', 'type' => LocationType::Level, 'parent_id' => $building->id]
        );

        $room = Location::firstOrCreate(
            ['name' => 'Store A', 'type' => LocationType::Room, 'parent_id' => $level->id]
        );

        Location::firstOrCreate(
            ['name' => 'Rack 01', 'type' => LocationType::Rack, 'parent_id' => $room->id]
        );

        Location::firstOrCreate(
            ['name' => 'Portable Pool', 'type' => LocationType::Site],
            ['is_portable' => true]
        );
    }
}
