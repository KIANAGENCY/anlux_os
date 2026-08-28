<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea columnas de entrega por equipo sin depender de SSH/artisan migrate.
 * Idempotente: solo agrega lo que falta.
 */
final class EquiposOrdenEntregaSchema
{
    public static function ensure(): void
    {
        if (! Schema::hasTable('equipos_orden')) {
            return;
        }

        if (! Schema::hasColumn('equipos_orden', 'entrega_receptor_tipo')) {
            Schema::table('equipos_orden', function (Blueprint $table): void {
                if (Schema::hasColumn('equipos_orden', 'acciones')) {
                    $table->string('entrega_receptor_tipo', 20)->nullable()->after('acciones');
                } else {
                    $table->string('entrega_receptor_tipo', 20)->nullable();
                }
            });
        }

        if (! Schema::hasColumn('equipos_orden', 'entrega_recibido_cliente')) {
            Schema::table('equipos_orden', function (Blueprint $table): void {
                if (Schema::hasColumn('equipos_orden', 'entrega_receptor_tipo')) {
                    $table->text('entrega_recibido_cliente')->nullable()->after('entrega_receptor_tipo');
                } else {
                    $table->text('entrega_recibido_cliente')->nullable();
                }
            });
        }

        if (! Schema::hasColumn('equipos_orden', 'entrega_firma_cliente')) {
            Schema::table('equipos_orden', function (Blueprint $table): void {
                if (Schema::hasColumn('equipos_orden', 'entrega_recibido_cliente')) {
                    $table->text('entrega_firma_cliente')->nullable()->after('entrega_recibido_cliente');
                } else {
                    $table->text('entrega_firma_cliente')->nullable();
                }
            });
        }

        if (! Schema::hasColumn('equipos_orden', 'entrega_firma_tecnico')) {
            Schema::table('equipos_orden', function (Blueprint $table): void {
                if (Schema::hasColumn('equipos_orden', 'entrega_firma_cliente')) {
                    $table->text('entrega_firma_tecnico')->nullable()->after('entrega_firma_cliente');
                } else {
                    $table->text('entrega_firma_tecnico')->nullable();
                }
            });
        }

        if (! Schema::hasColumn('equipos_orden', 'entrega_tecnico')) {
            Schema::table('equipos_orden', function (Blueprint $table): void {
                if (Schema::hasColumn('equipos_orden', 'entrega_firma_tecnico')) {
                    $table->text('entrega_tecnico')->nullable()->after('entrega_firma_tecnico');
                } else {
                    $table->text('entrega_tecnico')->nullable();
                }
            });
        }

        if (! Schema::hasColumn('equipos_orden', 'entrega_fecha')) {
            Schema::table('equipos_orden', function (Blueprint $table): void {
                if (Schema::hasColumn('equipos_orden', 'entrega_tecnico')) {
                    $table->dateTime('entrega_fecha')->nullable()->after('entrega_tecnico');
                } else {
                    $table->dateTime('entrega_fecha')->nullable();
                }
            });
        }
    }
}
