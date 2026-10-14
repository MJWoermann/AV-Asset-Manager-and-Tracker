<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32);
            $table->unsignedInteger('quantity_before');
            $table->unsignedInteger('quantity_after');
            $table->integer('quantity_delta');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('external_po_number')->nullable();
            $table->string('external_po_url')->nullable();
            $table->text('notes')->nullable();
            $table->string('source', 32);
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['asset_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
