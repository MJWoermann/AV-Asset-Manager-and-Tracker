<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('custom_field_sets', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('custom_field_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_field_set_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('type', 32)->default('text'); // text, number, checkbox, select
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['custom_field_set_id', 'slug']);
        });

        Schema::create('item_type_field_set', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('custom_field_set_id')->constrained()->cascadeOnDelete();
            $table->unique(['item_type_id', 'custom_field_set_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_type_field_set');
        Schema::dropIfExists('custom_field_definitions');
        Schema::dropIfExists('custom_field_sets');
        Schema::dropIfExists('item_types');
    }
};
