<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_lists', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 32); // inventory, event
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['type', 'name']);
        });

        Schema::create('asset_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_list_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->boolean('is_child_expand')->default(false);
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['asset_list_id', 'asset_id']);
        });

        Schema::create('scan_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_list_id')->constrained('asset_lists')->cascadeOnDelete();
            $table->foreignId('inventory_list_id')->nullable()->constrained('asset_lists')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 32)->default('active'); // active, closed
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_sessions');
        Schema::dropIfExists('asset_list_items');
        Schema::dropIfExists('asset_lists');
    }
};
