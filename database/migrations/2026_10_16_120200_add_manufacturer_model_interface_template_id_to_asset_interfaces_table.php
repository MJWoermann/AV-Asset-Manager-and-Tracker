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
                ->after('item_type_interface_template_id')
                ->constrained('manufacturer_model_interface_templates')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('asset_interfaces', function (Blueprint $table) {
            $table->dropConstrainedForeignId('manufacturer_model_interface_template_id');
        });
    }
};
