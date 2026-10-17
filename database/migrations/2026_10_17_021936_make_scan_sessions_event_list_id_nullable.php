<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scan_sessions', function (Blueprint $table) {
            $table->dropForeign(['event_list_id']);
        });

        Schema::table('scan_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('event_list_id')->nullable()->change();
        });

        Schema::table('scan_sessions', function (Blueprint $table) {
            $table->foreign('event_list_id')
                ->references('id')
                ->on('asset_lists')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('scan_sessions', function (Blueprint $table) {
            $table->dropForeign(['event_list_id']);
        });

        Schema::table('scan_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('event_list_id')->nullable(false)->change();
        });

        Schema::table('scan_sessions', function (Blueprint $table) {
            $table->foreign('event_list_id')
                ->references('id')
                ->on('asset_lists')
                ->cascadeOnDelete();
        });
    }
};
