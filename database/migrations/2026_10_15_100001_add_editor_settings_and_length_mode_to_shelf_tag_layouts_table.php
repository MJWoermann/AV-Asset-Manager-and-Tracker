<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shelf_tag_layouts', function (Blueprint $table) {
            $table->string('length_mode', 16)->default('fixed')->after('orientation');
            $table->json('editor_settings')->nullable()->after('elements');
        });
    }

    public function down(): void
    {
        Schema::table('shelf_tag_layouts', function (Blueprint $table) {
            $table->dropColumn(['length_mode', 'editor_settings']);
        });
    }
};
