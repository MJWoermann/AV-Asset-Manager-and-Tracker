<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manufacturer_model_interface_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manufacturer_model_id');
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('connection_type_id')->nullable();
            $table->timestamps();

            // Explicit short names: auto {table}_{column}_foreign exceeds MySQL's 64-char limit.
            $table->foreign('manufacturer_model_id', 'mm_iface_tpl_model_id_fk')
                ->references('id')
                ->on('manufacturer_models')
                ->cascadeOnDelete();
            $table->foreign('connection_type_id', 'mm_iface_tpl_conn_type_fk')
                ->references('id')
                ->on('connection_types')
                ->nullOnDelete();
            $table->index(['manufacturer_model_id', 'sort_order'], 'mm_iface_tpl_model_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manufacturer_model_interface_templates');
    }
};
