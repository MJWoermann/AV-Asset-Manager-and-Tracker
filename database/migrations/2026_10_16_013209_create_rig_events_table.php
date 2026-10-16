<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rig_events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('venue_space')->nullable();
            $table->date('event_date')->nullable();
            $table->string('status')->default('draft');
            $table->boolean('engineer_required')->default(false);
            $table->boolean('engineer_acquired')->default(false);
            $table->text('engineer_notes')->nullable();
            $table->string('structure_signed_name')->nullable();
            $table->timestamp('structure_signed_at')->nullable();
            $table->text('structure_notes')->nullable();
            $table->json('process_checklist')->nullable();
            $table->text('variations_notes')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('asset_list_id')->nullable()->constrained('asset_lists')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'event_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rig_events');
    }
};
