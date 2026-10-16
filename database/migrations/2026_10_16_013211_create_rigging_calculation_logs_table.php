<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rigging_calculation_logs', function (Blueprint $table) {
            $table->id();
            $table->string('calculator');
            $table->string('title')->nullable();
            $table->json('inputs');
            $table->json('results');
            $table->json('units')->nullable();
            $table->foreignId('rig_event_id')->nullable()->constrained('rig_events')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['calculator', 'created_at']);
            $table->index('rig_event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rigging_calculation_logs');
    }
};
