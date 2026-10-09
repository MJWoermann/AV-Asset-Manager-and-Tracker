<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scan_sessions', function (Blueprint $table) {
            $table->string('asset_status', 32)->nullable()->after('location_id');
        });
    }

    public function down(): void
    {
        Schema::table('scan_sessions', function (Blueprint $table) {
            $table->dropColumn('asset_status');
        });
    }
};
