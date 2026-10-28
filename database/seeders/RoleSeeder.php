<?php

namespace Database\Seeders;

use App\Support\AccessPermissions;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        AccessPermissions::sync();
    }
}
