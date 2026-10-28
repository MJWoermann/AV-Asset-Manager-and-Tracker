<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scan_sessions', function (Blueprint $table) {
            $table->foreignId('parent_asset_id')->nullable()->after('asset_status')->constrained('assets')->nullOnDelete();
            $table->boolean('replace_existing_parent')->default(false)->after('parent_asset_id');
        });
    }

    public function down(): void
    {
        Schema::table('scan_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_asset_id');
            $table->dropColumn('replace_existing_parent');
        });
    }
};
