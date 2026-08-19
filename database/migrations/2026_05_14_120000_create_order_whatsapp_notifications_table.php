<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('order_whatsapp_notifications')) {
            return;
        }

        Schema::create('order_whatsapp_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('id_orden_c');
            $table->string('folio', 50)->nullable();
            $table->string('estatus', 50);
            $table->string('telefono', 32)->nullable();
            $table->string('template_name', 120)->nullable();
            $table->string('provider_message_id', 160)->nullable();
            $table->string('status', 40);
            $table->text('message')->nullable();
            $table->longText('payload_json')->nullable();
            $table->longText('response_json')->nullable();
            $table->longText('webhook_json')->nullable();
            $table->dateTime('queued_at')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->dateTime('read_at')->nullable();
            $table->dateTime('failed_at')->nullable();
            $table->timestamps();

            $table->index(['id_orden_c', 'estatus'], 'idx_order_whatsapp_order_status');
            $table->index('provider_message_id', 'idx_order_whatsapp_provider_message');
            $table->index('status', 'idx_order_whatsapp_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_whatsapp_notifications');
    }
};
