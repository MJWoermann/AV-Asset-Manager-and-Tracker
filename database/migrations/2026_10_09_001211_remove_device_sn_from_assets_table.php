<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('assets', 'device_sn')) {
            return;
        }

        DB::table('assets')
            ->whereNotNull('device_sn')
            ->where('device_sn', '!=', '')
            ->where(function ($query) {
                $query->whereNull('serial_number')
                    ->orWhere('serial_number', '');
            })
            ->update([
                'serial_number' => DB::raw('device_sn'),
            ]);

        Schema::table('assets', function (Blueprint $table) {
            $table->dropIndex(['device_sn']);
            $table->dropColumn('device_sn');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->string('device_sn')->nullable()->after('rig_tag');
            $table->index('device_sn');
        });
    }
};
