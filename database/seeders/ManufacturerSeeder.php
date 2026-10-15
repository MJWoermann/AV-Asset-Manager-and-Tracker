<?php

namespace Database\Seeders;

use App\Models\Manufacturer;
use App\Services\ManufacturerCatalogueService;
use Illuminate\Database\Seeder;

class ManufacturerSeeder extends Seeder
{
    public function run(): void
    {
        $catalogue = app(ManufacturerCatalogueService::class);

        $entries = [
            'TP Cases' => ['RC-42U'],
            'Chauvet' => ['COLORado'],
            'Yamaha' => ['QL1'],
        ];

        foreach ($entries as $manufacturerName => $models) {
            $manufacturer = $catalogue->resolveManufacturer($manufacturerName);
            foreach ($models as $modelName) {
                $catalogue->resolveModel($manufacturer, $modelName);
            }
        }

        Manufacturer::query()->firstOrCreate(['name' => 'Generic']);
    }
}
