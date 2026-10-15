<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_interfaces', function (Blueprint $table) {
            $table->foreignId('connection_type_id')
                ->nullable()
                ->after('name')
                ->constrained('connection_types')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('asset_interfaces', function (Blueprint $table) {
            $table->dropConstrainedForeignId('connection_type_id');
        });
    }
};
