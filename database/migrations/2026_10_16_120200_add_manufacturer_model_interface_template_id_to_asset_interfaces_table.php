<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_interfaces', function (Blueprint $table) {
            $table->foreignId('manufacturer_model_interface_template_id')
                ->nullable()
                ->after('item_type_interface_template_id');

            // Explicit short name: auto {table}_{column}_foreign exceeds MySQL's 64-char limit.
            $table->foreign('manufacturer_model_interface_template_id', 'ai_mm_iface_tpl_id_fk')
                ->references('id')
                ->on('manufacturer_model_interface_templates')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('asset_interfaces', function (Blueprint $table) {
            $table->dropForeign('ai_mm_iface_tpl_id_fk');
            $table->dropColumn('manufacturer_model_interface_template_id');
        });
    }
};
