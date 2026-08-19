<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orden_servicio_c')) {
            return;
        }

        Schema::table('orden_servicio_c', function (Blueprint $table): void {
            if (! Schema::hasColumn('orden_servicio_c', 'salida_temporal_activa')) {
                $table->boolean('salida_temporal_activa')->default(false)->after('estatus');
            }
            if (! Schema::hasColumn('orden_servicio_c', 'fecha_salida_temporal')) {
                $table->dateTime('fecha_salida_temporal')->nullable()->after('salida_temporal_activa');
            }
            if (! Schema::hasColumn('orden_servicio_c', 'fecha_regreso_temporal')) {
                $table->dateTime('fecha_regreso_temporal')->nullable()->after('fecha_salida_temporal');
            }
            if (! Schema::hasColumn('orden_servicio_c', 'motivo_salida_temporal')) {
                $table->text('motivo_salida_temporal')->nullable()->after('fecha_regreso_temporal');
            }
            if (! Schema::hasColumn('orden_servicio_c', 'firma_c_salida_temp')) {
                $table->string('firma_c_salida_temp', 255)->nullable()->after('motivo_salida_temporal');
            }
            if (! Schema::hasColumn('orden_servicio_c', 'firma_t_salida_temp')) {
                $table->string('firma_t_salida_temp', 255)->nullable()->after('firma_c_salida_temp');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('orden_servicio_c')) {
            return;
        }

        Schema::table('orden_servicio_c', function (Blueprint $table): void {
            foreach ([
                'firma_t_salida_temp',
                'firma_c_salida_temp',
                'motivo_salida_temporal',
                'fecha_regreso_temporal',
                'fecha_salida_temporal',
                'salida_temporal_activa',
            ] as $col) {
                if (Schema::hasColumn('orden_servicio_c', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
