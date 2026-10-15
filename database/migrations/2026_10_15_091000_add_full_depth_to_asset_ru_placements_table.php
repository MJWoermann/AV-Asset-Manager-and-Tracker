<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_ru_placements', function (Blueprint $table) {
            $table->boolean('full_depth')->default(false)->after('ru_height');
        });
    }

    public function down(): void
    {
        Schema::table('asset_ru_placements', function (Blueprint $table) {
            $table->dropColumn('full_depth');
        });
    }
};
