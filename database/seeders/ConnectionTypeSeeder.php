<?php

namespace Database\Seeders;

use App\Models\ConnectionType;
use Illuminate\Database\Seeder;

class ConnectionTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => 'Network',
                'slug' => 'network',
                'color' => '#2563eb',
                'icon' => 'network',
                'allows_protocol' => false,
                'sort_order' => 10,
            ],
            [
                'name' => 'HDMI',
                'slug' => 'hdmi',
                'color' => '#00adb7',
                'icon' => 'hdmi',
                'allows_protocol' => false,
                'sort_order' => 20,
            ],
            [
                'name' => 'SDI',
                'slug' => 'sdi',
                'color' => '#dc2626',
                'icon' => 'sdi',
                'allows_protocol' => false,
                'sort_order' => 30,
            ],
            [
                'name' => 'Dante Network',
                'slug' => 'dante-network',
                'color' => '#7c3aed',
                'icon' => 'dante',
                'allows_protocol' => false,
                'sort_order' => 40,
            ],
            [
                'name' => 'Custom Protocol',
                'slug' => 'custom-protocol',
                'color' => '#ea580c',
                'icon' => 'custom',
                'allows_protocol' => true,
                'sort_order' => 50,
            ],
            [
                'name' => 'Analog',
                'slug' => 'analog',
                'color' => '#64748b',
                'icon' => 'analog',
                'allows_protocol' => false,
                'sort_order' => 60,
            ],
        ];

        foreach ($types as $type) {
            ConnectionType::query()->updateOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }
    }
}
