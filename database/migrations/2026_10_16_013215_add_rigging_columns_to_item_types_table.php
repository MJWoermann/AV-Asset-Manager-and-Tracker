<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_types', function (Blueprint $table) {
            $table->boolean('supports_rigging_calculator')->default(false)->after('supports_stock_tracking');
            $table->decimal('rigging_weight_kg', 12, 3)->nullable()->after('supports_rigging_calculator');
        });
    }

    public function down(): void
    {
        Schema::table('item_types', function (Blueprint $table) {
            $table->dropColumn(['supports_rigging_calculator', 'rigging_weight_kg']);
        });
    }
};
