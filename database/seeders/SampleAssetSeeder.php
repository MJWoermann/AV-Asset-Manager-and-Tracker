<?php

namespace Database\Seeders;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetList;
use App\Models\AssetListItem;
use App\Models\ItemType;
use App\Models\Location;
use App\Services\ManufacturerCatalogueService;
use Illuminate\Database\Seeder;

class SampleAssetSeeder extends Seeder
{
    public function run(): void
    {
        $rackType = ItemType::where('slug', 'rack-roadcase')->first();
        $lxType = ItemType::where('slug', 'lx-fixture')->first();
        $audioType = ItemType::where('slug', 'audio-equipment')->first();
        $location = Location::where('name', 'Rack 01')->first();

        if (! $rackType || ! $lxType || ! $location) {
            return;
        }

        $catalogue = app(ManufacturerCatalogueService::class);
        $tp = $catalogue->resolveFromNames('TP Cases', 'RC-42U');
        $chauvet = $catalogue->resolveFromNames('Chauvet', 'COLORado');
        $yamaha = $catalogue->resolveFromNames('Yamaha', 'QL1');

        $rack = Asset::firstOrCreate(
            ['tp_barcode' => 'RC-1001'],
            [
                'item_type_id' => $rackType->id,
                'location_id' => $location->id,
                'name' => 'Road Case Alpha',
                'status' => AssetStatus::Available,
                'manufacturer_id' => $tp['manufacturer_id'],
                'manufacturer_model_id' => $tp['manufacturer_model_id'],
                'fmi_ast' => 'FMI-RC-001',
                'rig_tag' => 'RIG-RC-01',
            ]
        );

        $children = [
            [
                'tp_barcode' => 'LX-2001',
                'name' => 'LED Wash 1',
                'item_type_id' => $lxType->id,
                'fmi_ast' => 'FMI-LX-001',
                'ip_address' => '10.10.5.21',
                'mac_address' => '00:1A:2B:3C:4D:01',
                'serial_number' => 'SN-LX-001',
            ],
            [
                'tp_barcode' => 'LX-2002',
                'name' => 'LED Wash 2',
                'item_type_id' => $lxType->id,
                'fmi_ast' => 'FMI-LX-002',
                'ip_address' => '10.10.5.22',
                'mac_address' => '00:1A:2B:3C:4D:02',
                'serial_number' => 'SN-LX-002',
            ],
        ];

        foreach ($children as $data) {
            Asset::firstOrCreate(
                ['tp_barcode' => $data['tp_barcode']],
                array_merge($data, [
                    'parent_id' => $rack->id,
                    'location_id' => $rack->location_id,
                    'status' => AssetStatus::Available,
                    'manufacturer_id' => $chauvet['manufacturer_id'],
                    'manufacturer_model_id' => $chauvet['manufacturer_model_id'],
                ])
            );
        }

        if ($audioType) {
            Asset::firstOrCreate(
                ['tp_barcode' => 'AU-3001'],
                [
                    'item_type_id' => $audioType->id,
                    'location_id' => $location->id,
                    'name' => 'Digital Mixer',
                    'status' => AssetStatus::InService,
                    'manufacturer_id' => $yamaha['manufacturer_id'],
                    'manufacturer_model_id' => $yamaha['manufacturer_model_id'],
                    'fmi_ast' => 'FMI-AU-001',
                    'serial_number' => 'SN-AU-001',
                    'ip_address' => '10.10.5.50',
                ]
            );
        }

        $inventory = AssetList::where('name', 'FMI Export')->first();
        if ($inventory) {
            foreach (Asset::all() as $asset) {
                AssetListItem::firstOrCreate(
                    ['asset_list_id' => $inventory->id, 'asset_id' => $asset->id],
                    ['quantity' => 1]
                );
            }
        }
    }
}
