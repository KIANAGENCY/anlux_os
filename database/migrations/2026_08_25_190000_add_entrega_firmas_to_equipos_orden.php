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

        if (! Schema::hasColumn('equipos_orden', 'entrega_firma_cliente')) {
            Schema::table('equipos_orden', function (Blueprint $table) {
                $table->text('entrega_firma_cliente')->nullable()->after('entrega_recibido_cliente');
            });
        }
        if (! Schema::hasColumn('equipos_orden', 'entrega_firma_tecnico')) {
            Schema::table('equipos_orden', function (Blueprint $table) {
                $table->text('entrega_firma_tecnico')->nullable()->after('entrega_firma_cliente');
            });
        }
        if (! Schema::hasColumn('equipos_orden', 'entrega_tecnico')) {
            Schema::table('equipos_orden', function (Blueprint $table) {
                $table->text('entrega_tecnico')->nullable()->after('entrega_firma_tecnico');
            });
        }
        if (! Schema::hasColumn('equipos_orden', 'entrega_fecha')) {
            Schema::table('equipos_orden', function (Blueprint $table) {
                $table->dateTime('entrega_fecha')->nullable()->after('entrega_tecnico');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('equipos_orden')) {
            return;
        }

        foreach (['entrega_fecha', 'entrega_tecnico', 'entrega_firma_tecnico', 'entrega_firma_cliente'] as $column) {
            if (Schema::hasColumn('equipos_orden', $column)) {
                Schema::table('equipos_orden', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
