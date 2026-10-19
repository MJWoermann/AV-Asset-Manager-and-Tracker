<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('item_types') || Schema::hasColumn('item_types', 'department_id')) {
            return;
        }

        $generalId = DB::table('departments')->where('slug', 'general')->value('id');

        if ($generalId === null) {
            $now = now();
            $generalId = DB::table('departments')->insertGetId([
                'name' => 'General',
                'slug' => 'general',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::table('item_types', function (Blueprint $table) use ($generalId): void {
            $table->unsignedBigInteger('department_id')->default($generalId);
            $table->foreign('department_id', 'item_types_dept_id_fk')
                ->references('id')->on('departments')->restrictOnDelete();
            $table->index('department_id', 'item_types_dept_id_idx');
        });

        DB::table('item_types')->whereNull('department_id')->update(['department_id' => $generalId]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('item_types') || ! Schema::hasColumn('item_types', 'department_id')) {
            return;
        }

        Schema::table('item_types', function (Blueprint $table): void {
            $table->dropForeign('item_types_dept_id_fk');
            $table->dropIndex('item_types_dept_id_idx');
            $table->dropColumn('department_id');
        });
    }
};
