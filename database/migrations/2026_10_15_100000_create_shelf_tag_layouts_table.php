<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shelf_tag_layouts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('paper_preset', 64);
            $table->decimal('width_mm', 8, 2);
            $table->decimal('height_mm', 8, 2);
            $table->string('orientation', 16)->default('portrait');
            $table->json('elements');
            $table->string('logo_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shelf_tag_layouts');
    }
};
