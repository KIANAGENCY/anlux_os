<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('equipos_orden')) {
            return;
        }

        if (! Schema::hasColumn('equipos_orden', 'entrega_receptor_tipo')) {
            Schema::table('equipos_orden', function (Blueprint $table) {
                $table->string('entrega_receptor_tipo', 20)->nullable()->after('acciones');
            });
        }

        if (! Schema::hasColumn('equipos_orden', 'entrega_recibido_cliente')) {
            Schema::table('equipos_orden', function (Blueprint $table) {
                $table->text('entrega_recibido_cliente')->nullable()->after('entrega_receptor_tipo');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('equipos_orden')) {
            return;
        }

        if (Schema::hasColumn('equipos_orden', 'entrega_recibido_cliente')) {
            Schema::table('equipos_orden', function (Blueprint $table) {
                $table->dropColumn('entrega_recibido_cliente');
            });
        }

        if (Schema::hasColumn('equipos_orden', 'entrega_receptor_tipo')) {
            Schema::table('equipos_orden', function (Blueprint $table) {
                $table->dropColumn('entrega_receptor_tipo');
            });
        }
    }
};
