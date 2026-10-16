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
            $table->foreignId('manufacturer_model_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('connection_type_id')
                ->nullable()
                ->constrained('connection_types')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['manufacturer_model_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manufacturer_model_interface_templates');
    }
};
