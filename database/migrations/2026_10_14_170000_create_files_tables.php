<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category', 32);
            $table->string('source_type', 16);
            $table->string('disk')->nullable();
            $table->string('path')->nullable();
            $table->string('content_hash', 64)->nullable()->unique();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('external_url', 2048)->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('file_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained('files')->cascadeOnDelete();
            $table->morphs('linkable');
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['file_id', 'linkable_type', 'linkable_id'], 'file_links_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_links');
        Schema::dropIfExists('files');
    }
};
