<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_ru_placements', function (Blueprint $table) {
            $table->string('width', 16)->default('full')->after('full_depth');
            $table->unsignedTinyInteger('slot')->default(1)->after('width');
        });
    }

    public function down(): void
    {
        Schema::table('asset_ru_placements', function (Blueprint $table) {
            $table->dropColumn(['width', 'slot']);
        });
    }
};
