<?php

namespace Database\Seeders;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetList;
use App\Models\AssetListItem;
use App\Models\ItemType;
use App\Models\Location;
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

        $rack = Asset::firstOrCreate(
            ['tp_barcode' => 'RC-1001'],
            [
                'item_type_id' => $rackType->id,
                'location_id' => $location->id,
                'name' => 'Road Case Alpha',
                'status' => AssetStatus::Available,
                'manufacturer' => 'TP Cases',
                'model' => 'RC-42U',
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
                'device_sn' => 'SN-LX-001',
            ],
            [
                'tp_barcode' => 'LX-2002',
                'name' => 'LED Wash 2',
                'item_type_id' => $lxType->id,
                'fmi_ast' => 'FMI-LX-002',
                'ip_address' => '10.10.5.22',
                'mac_address' => '00:1A:2B:3C:4D:02',
                'device_sn' => 'SN-LX-002',
            ],
        ];

        foreach ($children as $data) {
            Asset::firstOrCreate(
                ['tp_barcode' => $data['tp_barcode']],
                array_merge($data, [
                    'parent_id' => $rack->id,
                    'location_id' => $rack->location_id,
                    'status' => AssetStatus::Available,
                    'manufacturer' => 'Chauvet',
                    'model' => 'COLORado',
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
                    'manufacturer' => 'Yamaha',
                    'model' => 'QL1',
                    'fmi_ast' => 'FMI-AU-001',
                    'device_sn' => 'SN-AU-001',
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

        $stocktake = AssetList::where('name', 'Stocktake 2026')->first();
        if ($stocktake) {
            foreach (Asset::whereIn('tp_barcode', ['RC-1001', 'LX-2001', 'AU-3001'])->get() as $asset) {
                AssetListItem::firstOrCreate(
                    ['asset_list_id' => $stocktake->id, 'asset_id' => $asset->id],
                    ['quantity' => 1]
                );
            }
        }
    }
}
