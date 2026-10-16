<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_type_aliases', function (Blueprint $table) {
            $table->id();
            $table->string('former_name');
            $table->string('former_slug');
            $table->foreignId('item_type_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique('former_name');
            $table->unique('former_slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_type_aliases');
    }
};
