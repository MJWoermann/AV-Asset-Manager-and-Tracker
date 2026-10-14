<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('connection_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color', 7);
            $table->string('icon', 32);
            $table->boolean('allows_protocol')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('item_type_interface_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_type_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['item_type_id', 'sort_order']);
        });

        Schema::create('asset_interfaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('item_type_interface_template_id')
                ->nullable()
                ->constrained('item_type_interface_templates')
                ->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['asset_id', 'sort_order']);
        });

        Schema::create('asset_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interface_a_id')->constrained('asset_interfaces')->cascadeOnDelete();
            $table->foreignId('interface_b_id')->constrained('asset_interfaces')->cascadeOnDelete();
            $table->foreignId('connection_type_id')->constrained('connection_types')->restrictOnDelete();
            $table->string('protocol')->nullable();
            $table->string('label')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_connections');
        Schema::dropIfExists('asset_interfaces');
        Schema::dropIfExists('item_type_interface_templates');
        Schema::dropIfExists('connection_types');
    }
};
