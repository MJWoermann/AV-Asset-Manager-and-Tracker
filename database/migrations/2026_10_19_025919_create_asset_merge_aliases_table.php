<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_merge_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survivor_asset_id');
            $table->foreign('survivor_asset_id', 'ama_survivor_asset_fk')
                ->references('id')
                ->on('assets')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('merged_asset_id')->nullable();
            $table->foreignId('department_id')->nullable();
            $table->foreign('department_id', 'ama_department_fk')
                ->references('id')
                ->on('departments')
                ->nullOnDelete();
            $table->string('field', 32);
            $table->string('value');
            $table->string('value_normalized');
            $table->timestamps();

            $table->unique(['field', 'value_normalized'], 'ama_field_value_norm_unique');
            $table->index(['field', 'value'], 'ama_field_value_idx');
            $table->index('merged_asset_id', 'ama_merged_asset_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_merge_aliases');
    }
};
