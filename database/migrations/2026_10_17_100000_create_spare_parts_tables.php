<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spare_parts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('kind', 32);
            $table->foreignId('manufacturer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('manufacturer_model_id')->nullable();
            $table->foreign('manufacturer_model_id', 'spare_parts_mm_id_fk')
                ->references('id')->on('manufacturer_models')->nullOnDelete();
            $table->string('product_code')->nullable();
            $table->string('component');
            $table->unsignedInteger('quantity_deployed')->default(0);
            $table->unsignedInteger('quantity_on_hand')->default(0);
            $table->unsignedInteger('min_holding')->nullable();
            $table->unsignedInteger('max_holding')->nullable();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->string('shelf_location')->nullable();
            $table->string('stock_status', 32)->default('in_stock');
            $table->string('standby_mode', 16)->default('none');
            $table->string('supplier')->nullable();
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->string('lead_time')->nullable();
            $table->string('replacement_time')->nullable();
            $table->unsignedInteger('lead_time_days')->nullable();
            $table->unsignedInteger('replacement_time_minutes')->nullable();
            $table->string('severity', 32)->default('moderate');
            $table->string('lifecycle_status', 32)->default('active');
            $table->unsignedBigInteger('superseded_by_id')->nullable();
            $table->timestamp('superseded_at')->nullable();
            $table->timestamp('ordered_at')->nullable();
            $table->timestamp('expected_arrival_at')->nullable();
            $table->foreignId('asset_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('shelf_tag_layout_id')->nullable();
            $table->foreign('shelf_tag_layout_id', 'spare_parts_layout_id_fk')
                ->references('id')->on('shelf_tag_layouts')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('superseded_by_id', 'spare_parts_superseded_by_fk')
                ->references('id')->on('spare_parts')->nullOnDelete();
            $table->index(['lifecycle_status', 'kind'], 'spare_parts_lifecycle_kind_idx');
            $table->index('manufacturer_model_id', 'spare_parts_mm_id_idx');
        });

        Schema::create('spare_part_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spare_part_id');
            $table->foreign('spare_part_id', 'spm_spare_part_id_fk')
                ->references('id')->on('spare_parts')->cascadeOnDelete();
            $table->string('type', 32);
            $table->unsignedInteger('quantity_before');
            $table->unsignedInteger('quantity_after');
            $table->integer('quantity_delta');
            $table->foreignId('against_asset_id')->nullable();
            $table->foreign('against_asset_id', 'spm_against_asset_id_fk')
                ->references('id')->on('assets')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->string('ticket_ref')->nullable();
            $table->string('quoting_status')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['spare_part_id', 'recorded_at'], 'spm_part_recorded_idx');
            $table->index('against_asset_id', 'spm_against_asset_idx');
        });

        Schema::create('spare_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('spare_group_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spare_group_id');
            $table->foreign('spare_group_id', 'sgi_spare_group_id_fk')
                ->references('id')->on('spare_groups')->cascadeOnDelete();
            $table->foreignId('spare_part_id');
            $table->foreign('spare_part_id', 'sgi_spare_part_id_fk')
                ->references('id')->on('spare_parts')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['spare_group_id', 'spare_part_id'], 'sgi_group_part_unique');
        });

        Schema::create('spare_part_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spare_part_id');
            $table->foreign('spare_part_id', 'spm_models_part_id_fk')
                ->references('id')->on('spare_parts')->cascadeOnDelete();
            $table->foreignId('manufacturer_model_id');
            $table->foreign('manufacturer_model_id', 'spm_models_mm_id_fk')
                ->references('id')->on('manufacturer_models')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['spare_part_id', 'manufacturer_model_id'], 'spm_models_unique');
        });

        Schema::create('spare_part_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spare_part_id');
            $table->foreign('spare_part_id', 'spa_spare_part_id_fk')
                ->references('id')->on('spare_parts')->cascadeOnDelete();
            $table->foreignId('asset_id');
            $table->foreign('asset_id', 'spa_asset_id_fk')
                ->references('id')->on('assets')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['spare_part_id', 'asset_id'], 'spa_part_asset_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spare_part_assets');
        Schema::dropIfExists('spare_part_models');
        Schema::dropIfExists('spare_group_items');
        Schema::dropIfExists('spare_groups');
        Schema::dropIfExists('spare_part_movements');
        Schema::table('spare_parts', function (Blueprint $table) {
            $table->dropForeign('spare_parts_superseded_by_fk');
            $table->dropForeign('spare_parts_mm_id_fk');
            $table->dropForeign('spare_parts_layout_id_fk');
        });
        Schema::dropIfExists('spare_parts');
    }
};
