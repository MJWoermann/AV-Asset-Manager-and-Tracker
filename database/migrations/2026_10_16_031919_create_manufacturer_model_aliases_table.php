<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manufacturer_model_aliases', function (Blueprint $table) {
            $table->id();
            $table->string('former_name');
            $table->foreignId('manufacturer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('manufacturer_model_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['former_name', 'manufacturer_id'], 'mfr_model_alias_name_mfr_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manufacturer_model_aliases');
    }
};
