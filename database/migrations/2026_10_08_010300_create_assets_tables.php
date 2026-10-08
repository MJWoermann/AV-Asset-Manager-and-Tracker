<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->string('name');
            $table->string('status', 32)->default('available');
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('warranty_expiry')->nullable();
            $table->string('supplier')->nullable();
            $table->decimal('replacement_cost', 12, 2)->nullable();
            $table->string('fmi_ast')->nullable();
            $table->string('tp_barcode')->nullable();
            $table->string('rig_tag')->nullable();
            $table->string('device_sn')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('mac_address', 32)->nullable();
            $table->date('test_tag_expiry')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('parent_id');
            $table->index('location_id');
            $table->index('item_type_id');
            $table->index('fmi_ast');
            $table->index('tp_barcode');
            $table->index('rig_tag');
            $table->index('device_sn');
            $table->index('serial_number');
            $table->index('ip_address');
            $table->index('mac_address');
            $table->index('name');
        });

        Schema::create('asset_custom_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('custom_field_definition_id')->constrained()->cascadeOnDelete();
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['asset_id', 'custom_field_definition_id'], 'asset_cf_unique');
            $table->index('value');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_custom_field_values');
        Schema::dropIfExists('assets');
    }
};
