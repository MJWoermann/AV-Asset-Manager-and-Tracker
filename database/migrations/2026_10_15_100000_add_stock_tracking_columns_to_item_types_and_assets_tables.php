<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_types', function (Blueprint $table) {
            $table->boolean('supports_stock_tracking')->default(false)->after('supports_ru_visualiser');
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->unsignedInteger('min_stock_level')->nullable()->after('quantity');
            $table->unsignedInteger('max_stock_level')->nullable()->after('min_stock_level');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn(['min_stock_level', 'max_stock_level']);
        });

        Schema::table('item_types', function (Blueprint $table) {
            $table->dropColumn('supports_stock_tracking');
        });
    }
};
