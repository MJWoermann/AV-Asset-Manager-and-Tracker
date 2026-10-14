<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unknown_scans', function (Blueprint $table) {
            $table->id();
            $table->string('scanned_code');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scan_session_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('event_list_id')->nullable()->constrained('asset_lists')->nullOnDelete();
            $table->foreignId('inventory_list_id')->nullable()->constrained('asset_lists')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('asset_status', 64)->nullable();
            $table->text('note')->nullable();
            $table->foreignId('resolved_asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['scanned_code']);
            $table->index(['scan_session_id', 'created_at']);
            $table->index(['created_at']);
            $table->index(['resolved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unknown_scans');
    }
};
