<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('connection_buses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connection_type_id')->constrained('connection_types')->restrictOnDelete();
            $table->string('name');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['connection_type_id', 'name'], 'conn_buses_type_name_idx');
        });

        Schema::create('connection_bus_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connection_bus_id')->constrained('connection_buses')->cascadeOnDelete();
            $table->foreignId('asset_interface_id')->constrained('asset_interfaces')->cascadeOnDelete();
            $table->timestamps();

            $table->unique('asset_interface_id', 'conn_bus_members_iface_uq');
            $table->index('connection_bus_id', 'conn_bus_members_bus_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connection_bus_members');
        Schema::dropIfExists('connection_buses');
    }
};
