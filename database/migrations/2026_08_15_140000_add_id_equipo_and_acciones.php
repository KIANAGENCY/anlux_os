<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add id_equipo to trabajos_orden
        if (Schema::hasTable('trabajos_orden') && ! Schema::hasColumn('trabajos_orden', 'id_equipo')) {
            Schema::table('trabajos_orden', function (Blueprint $table) {
                $table->unsignedInteger('id_equipo')->nullable()->after('id_trabajo_detalle');
            });
        }

        // Add id_equipo to materiales_orden
        if (Schema::hasTable('materiales_orden') && ! Schema::hasColumn('materiales_orden', 'id_equipo')) {
            Schema::table('materiales_orden', function (Blueprint $table) {
                $table->unsignedInteger('id_equipo')->nullable()->after('id_material');
            });
        }

        // Add acciones column to equipos_orden (tinyint 1 for select/deselect)
        if (Schema::hasTable('equipos_orden') && ! Schema::hasColumn('equipos_orden', 'acciones')) {
            Schema::table('equipos_orden', function (Blueprint $table) {
                $table->unsignedTinyInteger('acciones')->default(0)->after('modelo');
            });
        }
    }

    public function down(): void
    {
        // Remove columns if needed
        if (Schema::hasTable('trabajos_orden') && Schema::hasColumn('trabajos_orden', 'id_equipo')) {
            Schema::table('trabajos_orden', function (Blueprint $table) {
                $table->dropColumn('id_equipo');
            });
        }

        if (Schema::hasTable('materiales_orden') && Schema::hasColumn('materiales_orden', 'id_equipo')) {
            Schema::table('materiales_orden', function (Blueprint $table) {
                $table->dropColumn('id_equipo');
            });
        }

        if (Schema::hasTable('equipos_orden') && Schema::hasColumn('equipos_orden', 'acciones')) {
            Schema::table('equipos_orden', function (Blueprint $table) {
                $table->dropColumn('acciones');
            });
        }
    }
};