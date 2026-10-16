<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rig_event_motor_patches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rig_event_id')->constrained('rig_events')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('motor_label');
            $table->string('patch_to')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['rig_event_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rig_event_motor_patches');
    }
};
