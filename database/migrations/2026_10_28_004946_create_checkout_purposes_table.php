<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkout_purposes', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 32)->unique();
            $table->string('name', 100);
            $table->string('behavior', 16);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();

        DB::table('checkout_purposes')->insert([
            [
                'slug' => 'hire',
                'name' => 'Hire',
                'behavior' => 'on_event',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'repair',
                'name' => 'Repair',
                'behavior' => 'repair',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'other',
                'name' => 'Other',
                'behavior' => 'on_event',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // asset_checkouts.purpose stores this slug. A foreign key would make
        // SQLite rebuild that table, which can drop existing checkout rows.
    }

    public function down(): void
    {
        Schema::dropIfExists('checkout_purposes');
    }
};
