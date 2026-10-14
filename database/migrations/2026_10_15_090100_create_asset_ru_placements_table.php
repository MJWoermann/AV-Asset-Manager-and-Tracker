<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_ru_placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rack_asset_id')->constrained('assets')->cascadeOnDelete();
            $table->string('face', 16);
            $table->unsignedSmallInteger('start_ru');
            $table->unsignedSmallInteger('ru_height');
            $table->string('kind', 16);
            $table->foreignId('child_asset_id')->nullable()->constrained('assets')->cascadeOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['rack_asset_id', 'face']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_ru_placements');
    }
};
