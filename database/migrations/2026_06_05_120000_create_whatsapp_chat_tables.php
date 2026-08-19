<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('wa_conversations')) {
            Schema::create('wa_conversations', function (Blueprint $table) {
                $table->id();
                $table->string('wa_phone', 32)->unique();
                $table->string('contact_name', 160)->nullable();
                $table->unsignedInteger('id_orden_c')->nullable();
                $table->string('last_order_folio', 50)->nullable();
                $table->dateTime('last_inbound_at')->nullable();
                $table->dateTime('last_message_at')->nullable();
                $table->string('last_message_preview', 280)->nullable();
                $table->string('last_message_direction', 8)->nullable();
                $table->unsignedInteger('unread_count')->default(0);
                $table->timestamps();

                $table->index('last_message_at', 'idx_wa_conv_last_message');
                $table->index('id_orden_c', 'idx_wa_conv_order');
            });
        }

        if (! Schema::hasTable('wa_messages')) {
            Schema::create('wa_messages', function (Blueprint $table) {
                $table->id();
                $table->string('wa_phone', 32);
                $table->string('direction', 8); // in | out
                $table->string('provider_message_id', 160)->nullable();
                $table->string('type', 32)->default('text');
                $table->text('body')->nullable();
                $table->string('status', 40)->default('received');
                $table->dateTime('wa_timestamp')->nullable();
                $table->unsignedInteger('sent_by_id_tecnico')->nullable();
                $table->string('error', 280)->nullable();
                $table->longText('raw_json')->nullable();
                $table->dateTime('delivered_at')->nullable();
                $table->dateTime('read_at')->nullable();
                $table->dateTime('failed_at')->nullable();
                $table->timestamps();

                $table->index(['wa_phone', 'id'], 'idx_wa_msg_phone_id');
                $table->index('provider_message_id', 'idx_wa_msg_provider');
                $table->index('status', 'idx_wa_msg_status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_messages');
        Schema::dropIfExists('wa_conversations');
    }
};
