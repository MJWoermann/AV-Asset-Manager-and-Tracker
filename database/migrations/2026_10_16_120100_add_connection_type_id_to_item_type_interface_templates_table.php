<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_type_interface_templates', function (Blueprint $table) {
            $table->foreignId('connection_type_id')
                ->nullable()
                ->after('notes')
                ->constrained('connection_types')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('item_type_interface_templates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('connection_type_id');
        });
    }
};
