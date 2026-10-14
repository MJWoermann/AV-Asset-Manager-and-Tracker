<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_types', function (Blueprint $table) {
            $table->boolean('supports_ru_visualiser')->default(false)->after('description');
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->unsignedSmallInteger('ru_height')->nullable()->after('notes');
            $table->boolean('ru_rear_enabled')->default(false)->after('ru_height');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn(['ru_height', 'ru_rear_enabled']);
        });

        Schema::table('item_types', function (Blueprint $table) {
            $table->dropColumn('supports_ru_visualiser');
        });
    }
};
