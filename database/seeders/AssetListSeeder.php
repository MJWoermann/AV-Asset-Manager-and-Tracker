<?php

namespace Database\Seeders;

use App\Models\AssetList;
use Illuminate\Database\Seeder;

class AssetListSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'FMI Export', 'type' => 'inventory'],
            ['name' => 'Stocktake 2025', 'type' => 'inventory'],
            ['name' => 'Stocktake 2026', 'type' => 'inventory'],
            ['name' => 'Stocktake 2027', 'type' => 'event'],
        ] as $list) {
            AssetList::firstOrCreate(
                ['name' => $list['name'], 'type' => $list['type']],
                ['description' => 'Seed '.$list['type'].' list']
            );
        }
    }
}
