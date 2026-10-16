<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_procedures', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->text('safety_notes')->nullable();
            $table->text('tools_required')->nullable();
            $table->unsignedInteger('estimated_minutes')->nullable();
            $table->foreignId('manufacturer_model_id')->nullable();
            $table->foreign('manufacturer_model_id', 'rp_proc_mm_id_fk')
                ->references('id')->on('manufacturer_models')->nullOnDelete();
            $table->foreignId('item_type_id')->nullable();
            $table->foreign('item_type_id', 'rp_proc_item_type_fk')
                ->references('id')->on('item_types')->nullOnDelete();
            $table->foreignId('spare_part_id')->nullable();
            $table->foreign('spare_part_id', 'rp_proc_spare_part_fk')
                ->references('id')->on('spare_parts')->nullOnDelete();
            $table->timestamps();

            $table->index('manufacturer_model_id', 'rp_proc_mm_id_idx');
            $table->index('item_type_id', 'rp_proc_item_type_idx');
            $table->index('spare_part_id', 'rp_proc_spare_part_idx');
        });

        Schema::create('repair_procedure_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_procedure_id');
            $table->foreign('repair_procedure_id', 'rp_pstep_proc_id_fk')
                ->references('id')->on('repair_procedures')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('title');
            $table->text('body')->nullable();
            $table->boolean('is_required')->default(true);
            $table->timestamps();

            $table->index(['repair_procedure_id', 'sort_order'], 'rp_pstep_proc_sort_idx');
        });

        Schema::create('repair_procedure_spare_part', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_procedure_id');
            $table->foreign('repair_procedure_id', 'rp_pspare_proc_id_fk')
                ->references('id')->on('repair_procedures')->cascadeOnDelete();
            $table->foreignId('spare_part_id');
            $table->foreign('spare_part_id', 'rp_pspare_part_id_fk')
                ->references('id')->on('spare_parts')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['repair_procedure_id', 'spare_part_id'], 'rp_pspare_unique');
        });

        Schema::create('repairs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id');
            $table->foreign('asset_id', 'repairs_asset_id_fk')
                ->references('id')->on('assets')->cascadeOnDelete();
            $table->foreignId('maintenance_log_id');
            $table->foreign('maintenance_log_id', 'repairs_maint_log_fk')
                ->references('id')->on('asset_maintenance_logs')->cascadeOnDelete();
            $table->foreignId('repair_procedure_id')->nullable();
            $table->foreign('repair_procedure_id', 'repairs_procedure_fk')
                ->references('id')->on('repair_procedures')->nullOnDelete();
            $table->string('status', 32)->default('open');
            $table->foreignId('assignee_user_id')->nullable();
            $table->foreign('assignee_user_id', 'repairs_assignee_fk')
                ->references('id')->on('users')->nullOnDelete();
            $table->string('summary');
            $table->text('notes')->nullable();
            $table->text('resolution_summary')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by_user_id')->nullable();
            $table->foreign('completed_by_user_id', 'repairs_completed_by_fk')
                ->references('id')->on('users')->nullOnDelete();
            $table->string('asset_status_before')->nullable();
            $table->string('asset_status_after')->nullable();
            $table->timestamps();

            $table->index(['status', 'started_at'], 'repairs_status_started_idx');
            $table->index('maintenance_log_id', 'repairs_maint_log_idx');
            $table->index('asset_id', 'repairs_asset_id_idx');
        });

        Schema::create('repair_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_id');
            $table->foreign('repair_id', 'repair_steps_repair_fk')
                ->references('id')->on('repairs')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('title');
            $table->text('body')->nullable();
            $table->boolean('is_required')->default(true);
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by_user_id')->nullable();
            $table->foreign('completed_by_user_id', 'repair_steps_done_by_fk')
                ->references('id')->on('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['repair_id', 'sort_order'], 'repair_steps_repair_sort_idx');
        });

        Schema::table('spare_part_movements', function (Blueprint $table) {
            $table->foreignId('repair_id')->nullable()->after('against_asset_id');
            $table->foreign('repair_id', 'spm_repair_id_fk')
                ->references('id')->on('repairs')->nullOnDelete();
            $table->index('repair_id', 'spm_repair_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('spare_part_movements', function (Blueprint $table) {
            $table->dropForeign('spm_repair_id_fk');
            $table->dropIndex('spm_repair_id_idx');
            $table->dropColumn('repair_id');
        });

        Schema::dropIfExists('repair_steps');
        Schema::dropIfExists('repairs');
        Schema::dropIfExists('repair_procedure_spare_part');
        Schema::dropIfExists('repair_procedure_steps');
        Schema::dropIfExists('repair_procedures');
    }
};
