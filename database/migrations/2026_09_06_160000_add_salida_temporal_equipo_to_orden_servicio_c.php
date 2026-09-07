<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('orden_servicio_c') && ! Schema::hasColumn('orden_servicio_c', 'salida_temporal_id_equipo')) {
            Schema::table('orden_servicio_c', function (Blueprint $table): void {
                $table->unsignedBigInteger('salida_temporal_id_equipo')->nullable()->after('salida_temporal_activa');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orden_servicio_c') && Schema::hasColumn('orden_servicio_c', 'salida_temporal_id_equipo')) {
            Schema::table('orden_servicio_c', function (Blueprint $table): void {
                $table->dropColumn('salida_temporal_id_equipo');
            });
        }
    }
};
