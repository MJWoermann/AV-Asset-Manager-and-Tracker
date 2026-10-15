<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_recipients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->boolean('is_active')->default(true);
            $table->string('role_tag')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('event_key');
            $table->string('name');
            $table->string('subject');
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->json('selected_fields')->nullable();
            $table->timestamps();

            $table->unique('event_key');
        });

        Schema::create('notification_template_recipient', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_template_id');
            $table->foreignId('notification_recipient_id');
            $table->json('required_fields')->nullable();
            $table->timestamps();

            // Explicit short names: auto {table}_{column}_foreign exceeds MySQL's 64-char limit.
            $table->foreign('notification_template_id', 'ntr_template_id_fk')
                ->references('id')
                ->on('notification_templates')
                ->cascadeOnDelete();
            $table->foreign('notification_recipient_id', 'ntr_recipient_id_fk')
                ->references('id')
                ->on('notification_recipients')
                ->cascadeOnDelete();

            $table->unique(
                ['notification_template_id', 'notification_recipient_id'],
                'notif_template_recipient_unique'
            );
        });

        Schema::create('outbound_email_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->string('event_key');
            $table->string('provider_message_id')->nullable();
            $table->string('conversation_id')->nullable();
            $table->string('subject');
            $table->json('to_addresses');
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('provider_message_id');
            $table->index('conversation_id');
        });

        Schema::create('inbound_email_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('priority')->default(100);
            $table->json('match_conditions')->nullable();
            $table->string('action')->default('create_log');
            $table->string('log_type')->default('note');
            $table->json('field_mapping')->nullable();
            $table->string('fault_status')->nullable();
            $table->boolean('require_review')->default(true);
            $table->timestamps();
        });

        Schema::create('inbound_email_actions', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('pending');
            $table->string('source_message_id')->unique();
            $table->string('from_address')->nullable();
            $table->string('subject')->nullable();
            $table->text('snippet')->nullable();
            $table->json('raw_headers')->nullable();
            $table->foreignId('inbound_email_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('outbound_email_thread_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('asset_maintenance_log_id')->nullable()->constrained()->nullOnDelete();
            $table->json('proposed_payload')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inbound_email_actions');
        Schema::dropIfExists('inbound_email_rules');
        Schema::dropIfExists('outbound_email_threads');
        Schema::dropIfExists('notification_template_recipient');
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('notification_recipients');
    }
};
