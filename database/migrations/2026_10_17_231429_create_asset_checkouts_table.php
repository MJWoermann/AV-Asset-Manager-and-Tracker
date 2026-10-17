<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_checkouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->nullable();
            $table->foreign('department_id', 'ast_co_dept_fk')
                ->references('id')->on('departments')->nullOnDelete();
            $table->foreignId('asset_id');
            $table->foreign('asset_id', 'ast_co_asset_fk')
                ->references('id')->on('assets')->cascadeOnDelete();
            $table->foreignId('checked_out_by_user_id');
            $table->foreign('checked_out_by_user_id', 'ast_co_by_user_fk')
                ->references('id')->on('users')->cascadeOnDelete();
            $table->foreignId('checked_out_to_user_id')->nullable();
            $table->foreign('checked_out_to_user_id', 'ast_co_to_user_fk')
                ->references('id')->on('users')->nullOnDelete();
            $table->string('checked_out_to_name')->nullable();
            $table->string('checked_out_to_email')->nullable();
            $table->string('checked_out_to_company')->nullable();
            $table->foreignId('location_id')->nullable();
            $table->foreign('location_id', 'ast_co_location_fk')
                ->references('id')->on('locations')->nullOnDelete();
            $table->timestamp('expected_return_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->foreignId('returned_by_user_id')->nullable();
            $table->foreign('returned_by_user_id', 'ast_co_ret_by_fk')
                ->references('id')->on('users')->nullOnDelete();
            $table->string('purpose', 32);
            $table->foreignId('repair_id')->nullable();
            $table->foreign('repair_id', 'ast_co_repair_fk')
                ->references('id')->on('repairs')->nullOnDelete();
            $table->string('asset_status_before')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['asset_id', 'returned_at'], 'ast_co_asset_ret_idx');
            $table->index('repair_id', 'ast_co_repair_idx');
            $table->index('checked_out_to_user_id', 'ast_co_to_user_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_checkouts');
    }
};
