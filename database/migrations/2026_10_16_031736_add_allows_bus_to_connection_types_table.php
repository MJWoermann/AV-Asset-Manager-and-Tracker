<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('connection_types', function (Blueprint $table) {
            $table->boolean('allows_bus')->default(false)->after('allows_protocol');
        });
    }

    public function down(): void
    {
        Schema::table('connection_types', function (Blueprint $table) {
            $table->dropColumn('allows_bus');
        });
    }
};
