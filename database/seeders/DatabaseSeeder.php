<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            ItemTypeSeeder::class,
            LocationSeeder::class,
            ManufacturerSeeder::class,
            ConnectionTypeSeeder::class,
            AssetListSeeder::class,
            SampleAssetSeeder::class,
            ShelfTagLayoutSeeder::class,
            EmailIntegrationSeeder::class,
        ]);
    }
}
