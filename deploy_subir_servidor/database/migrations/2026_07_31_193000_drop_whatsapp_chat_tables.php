<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('wa_messages');
        Schema::dropIfExists('wa_conversations');
    }

    public function down(): void
    {
        // Soporte WhatsApp eliminado; no se recrean las tablas.
    }
};
