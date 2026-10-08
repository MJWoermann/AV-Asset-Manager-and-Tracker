<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('name');
            $table->string('type', 32); // site, level, room, rack
            $table->boolean('is_portable')->default(false);
            $table->string('floorplan_path')->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamps();

            $table->index(['parent_id', 'type']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
