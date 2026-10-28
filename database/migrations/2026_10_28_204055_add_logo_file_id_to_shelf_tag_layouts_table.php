<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shelf_tag_layouts', function (Blueprint $table) {
            $table->foreignId('logo_file_id')
                ->nullable()
                ->after('logo_path')
                ->constrained('files')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shelf_tag_layouts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('logo_file_id');
        });
    }
};
