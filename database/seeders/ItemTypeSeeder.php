<?php

namespace Database\Seeders;

use App\Models\CustomFieldDefinition;
use App\Models\CustomFieldSet;
use App\Models\ItemType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ItemTypeSeeder extends Seeder
{
    public function run(): void
    {
        $sets = [
            'Network Details' => [
                ['name' => 'IP Address', 'slug' => 'ip_address', 'type' => 'text'],
                ['name' => 'MAC Address', 'slug' => 'mac_address', 'type' => 'text'],
                ['name' => 'DHCP Reserved', 'slug' => 'dhcp_reserved', 'type' => 'checkbox'],
            ],
            'Road Case & Rack Dimensions' => [
                ['name' => 'Width', 'slug' => 'width', 'type' => 'number'],
                ['name' => 'Height', 'slug' => 'height', 'type' => 'number'],
                ['name' => 'Depth', 'slug' => 'depth', 'type' => 'number'],
                ['name' => 'RU', 'slug' => 'ru', 'type' => 'number'],
            ],
            'DMX / sACN Details' => [
                ['name' => 'Host IP', 'slug' => 'host_ip', 'type' => 'text'],
                ['name' => 'Universe', 'slug' => 'universe', 'type' => 'number'],
                ['name' => 'Address', 'slug' => 'address', 'type' => 'number'],
                ['name' => 'Fixture Mode', 'slug' => 'fixture_mode', 'type' => 'text'],
            ],
            'Countable Variations' => [
                ['name' => 'Length', 'slug' => 'length', 'type' => 'text'],
                ['name' => 'Connector', 'slug' => 'connector', 'type' => 'text'],
            ],
        ];

        $createdSets = [];
        foreach ($sets as $setName => $fields) {
            $set = CustomFieldSet::firstOrCreate(
                ['slug' => Str::slug($setName)],
                ['name' => $setName]
            );
            foreach ($fields as $i => $field) {
                CustomFieldDefinition::firstOrCreate(
                    [
                        'custom_field_set_id' => $set->id,
                        'slug' => $field['slug'],
                    ],
                    [
                        'name' => $field['name'],
                        'type' => $field['type'],
                        'sort_order' => $i,
                    ]
                );
            }
            $createdSets[$setName] = $set;
        }

        $types = [
            ['name' => 'LX Fixture', 'slug' => 'lx-fixture', 'sets' => ['Network Details', 'DMX / sACN Details']],
            ['name' => 'Bulk Item', 'slug' => 'bulk-item', 'sets' => ['Countable Variations']],
            ['name' => 'Audio Equipment', 'slug' => 'audio-equipment', 'sets' => ['Network Details']],
            ['name' => 'Rack/RoadCase', 'slug' => 'rack-roadcase', 'sets' => ['Road Case & Rack Dimensions']],
            ['name' => 'Vision Item', 'slug' => 'vision-item', 'sets' => ['Network Details']],
        ];

        foreach ($types as $typeDef) {
            $type = ItemType::query()->where('slug', $typeDef['slug'])
                ->orWhere('name', $typeDef['name'])
                ->first();

            if ($type) {
                $type->update(['name' => $typeDef['name'], 'slug' => $typeDef['slug']]);
            } else {
                $type = ItemType::create([
                    'name' => $typeDef['name'],
                    'slug' => $typeDef['slug'],
                ]);
            }

            $ids = collect($typeDef['sets'])->map(fn ($n) => $createdSets[$n]->id)->all();
            $type->fieldSets()->syncWithoutDetaching($ids);
        }
    }
}
