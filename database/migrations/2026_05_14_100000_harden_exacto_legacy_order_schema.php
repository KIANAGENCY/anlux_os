<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('exacto_login_sequences')) {
            Schema::create('exacto_login_sequences', function (Blueprint $table) {
                $table->string('sequence_key', 80)->primary();
                $table->unsignedBigInteger('next_id');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('orden_folio_sequence')) {
            Schema::create('orden_folio_sequence', function (Blueprint $table) {
                $table->unsignedInteger('anio')->primary();
                $table->unsignedInteger('next_num');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('orden_servicio_c')) {
            Schema::create('orden_servicio_c', function (Blueprint $table) {
                $table->increments('id_orden_c');
                $table->string('folio', 50)->unique();
                $table->string('nombre_cliente', 255);
                $table->string('cliente_recibido', 255)->nullable();
                $table->string('tecnico_recibido', 255)->nullable();
                $table->string('tipo_servicio', 120)->nullable();
                $table->text('direccion')->nullable();
                $table->string('telefono', 255)->nullable();
                $table->string('correo', 255)->nullable();
                $table->string('poblacion', 255)->nullable();
                $table->dateTime('fecha_entrada')->nullable();
                $table->dateTime('fecha_terminada')->nullable();
                $table->dateTime('fecha_salida')->nullable();
                $table->unsignedInteger('no_equipo')->default(0);
                $table->text('observaciones')->nullable();
                $table->string('firma_c_e', 255)->nullable();
                $table->string('firma_t_r', 255)->nullable();
                $table->string('estatus', 50)->default('Recepción');
                $table->index('estatus');
                $table->index('fecha_entrada');
            });
        }

        if (! Schema::hasTable('orden_servicio_t')) {
            Schema::create('orden_servicio_t', function (Blueprint $table) {
                $table->increments('id_trabajo');
                $table->unsignedInteger('id_orden_c');
                $table->string('recibido_cliente', 255)->nullable();
                $table->string('tecnico_recibido', 255)->nullable();
                $table->string('entregado_por_tecnico', 255)->nullable();
                $table->decimal('subtotal_t', 12, 2)->default(0);
                $table->decimal('subtotal_m', 12, 2)->default(0);
                $table->decimal('iva', 12, 2)->default(0);
                $table->decimal('total_pagar', 12, 2)->default(0);
                $table->string('firma_c_r', 255)->nullable();
                $table->string('firma_t_e', 255)->nullable();
                $table->text('comentarios_m')->nullable();
                $table->index('id_orden_c', 'idx_orden_servicio_t_orden');
            });
        }

        if (! Schema::hasTable('equipos_orden')) {
            Schema::create('equipos_orden', function (Blueprint $table) {
                $table->increments('id_equipo');
                $table->unsignedInteger('id_orden_c');
                $table->string('marca', 120)->nullable();
                $table->string('modelo', 120)->nullable();
                $table->string('serie', 120)->nullable();
                $table->string('clave', 20)->nullable();
                $table->string('tipo_servicio', 120)->nullable();
                $table->text('descripcion_falla')->nullable();
                $table->index('id_orden_c', 'idx_equipos_orden_orden');
            });
        }

        if (! Schema::hasTable('trabajos_orden')) {
            Schema::create('trabajos_orden', function (Blueprint $table) {
                $table->increments('id_trabajo_detalle');
                $table->unsignedInteger('id_trabajo');
                $table->string('clave', 20)->nullable();
                $table->text('descripcion')->nullable();
                $table->decimal('importe', 12, 2)->default(0);
                $table->string('ticket', 80)->nullable();
                $table->index('id_trabajo', 'idx_trabajos_orden_trabajo');
            });
        }

        if (! Schema::hasTable('materiales_orden')) {
            Schema::create('materiales_orden', function (Blueprint $table) {
                $table->increments('id_material');
                $table->unsignedInteger('id_trabajo');
                $table->string('vale', 80)->nullable();
                $table->string('codigo', 80)->nullable();
                $table->decimal('cantidad', 12, 2)->default(0);
                $table->string('descripcion', 255)->nullable();
                $table->decimal('anticipo', 12, 2)->default(0);
                $table->decimal('precio_unitario', 12, 2)->default(0);
                $table->decimal('importe', 12, 2)->default(0);
                $table->string('ticket', 80)->nullable();
                $table->index('id_trabajo', 'idx_materiales_orden_trabajo');
            });
        }

        if (! Schema::hasTable('orden_servicio_tecnico_log')) {
            Schema::create('orden_servicio_tecnico_log', function (Blueprint $table) {
                $table->increments('id_log');
                $table->unsignedInteger('id_orden_c');
                $table->string('estatus', 50);
                $table->string('nombre_tecnico', 255);
                $table->dateTime('fecha');
                $table->index(['id_orden_c', 'fecha'], 'idx_orden_tecnico_log_orden_fecha');
            });
        }

        if (! Schema::hasTable('orden_servicio_nombre_busqueda')) {
            Schema::create('orden_servicio_nombre_busqueda', function (Blueprint $table) {
                $table->unsignedInteger('id_orden_c');
                $table->char('token', 64);
                $table->primary(['id_orden_c', 'token']);
                $table->index(['token', 'id_orden_c'], 'idx_orden_nombre_busqueda_token');
            });
        }

        if (! Schema::hasTable('orden_servicio_audit_log')) {
            Schema::create('orden_servicio_audit_log', function (Blueprint $table) {
                $table->increments('id_audit');
                $table->unsignedInteger('id_orden_c');
                $table->string('folio', 50)->nullable();
                $table->string('accion', 50);
                $table->string('usuario', 150)->nullable();
                $table->text('detalles')->nullable();
                $table->string('ip', 45)->nullable();
                $table->string('user_agent', 255)->nullable();
                $table->dateTime('fecha');
                $table->index(['id_orden_c', 'fecha'], 'idx_orden_audit_orden_fecha');
                $table->index(['accion', 'fecha'], 'idx_orden_audit_accion_fecha');
            });
        }

        if (! Schema::hasTable('security_activity_log')) {
            Schema::create('security_activity_log', function (Blueprint $table) {
                $table->id();
                $table->string('event_type', 100);
                $table->string('severity', 20)->default('info');
                $table->string('usuario', 150)->nullable();
                $table->string('ip', 45)->nullable();
                $table->string('uri', 255)->nullable();
                $table->string('user_agent', 255)->nullable();
                $table->text('details')->nullable();
                $table->dateTime('created_at');
                $table->index('created_at', 'idx_security_created_at');
                $table->index('event_type', 'idx_security_event');
                $table->index('severity', 'idx_security_severity');
            });
        }

        if (Schema::hasTable('trabajos_orden') && ! Schema::hasColumn('trabajos_orden', 'clave')) {
            Schema::table('trabajos_orden', function (Blueprint $table) {
                $table->string('clave', 20)->nullable()->after('id_trabajo');
            });
        }

        if (Schema::hasTable('trabajos_orden') && ! Schema::hasColumn('trabajos_orden', 'ticket')) {
            Schema::table('trabajos_orden', function (Blueprint $table) {
                $table->string('ticket', 80)->nullable()->after('importe');
            });
        }

        if (Schema::hasTable('login')) {
            $nextId = (int) (DB::table('login')->max('id_tecnico') ?? 0) + 1;
            $exists = DB::table('exacto_login_sequences')
                ->where('sequence_key', 'login_id_tecnico')
                ->exists();

            if (! $exists) {
                DB::table('exacto_login_sequences')->insert([
                    'sequence_key' => 'login_id_tecnico',
                    'next_id' => max(1, $nextId),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->syncMysqlLegacyColumns();
    }

    public function down(): void
    {
        Schema::dropIfExists('exacto_login_sequences');
        Schema::dropIfExists('security_activity_log');
        Schema::dropIfExists('orden_servicio_audit_log');
        Schema::dropIfExists('orden_servicio_nombre_busqueda');
        Schema::dropIfExists('orden_folio_sequence');
    }

    private function syncMysqlLegacyColumns(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach (
            [
                'orden_servicio_c' => ['nombre_cliente', 'cliente_recibido', 'correo', 'poblacion', 'telefono', 'firma_c_e', 'firma_t_r'],
                'orden_servicio_t' => ['recibido_cliente', 'firma_c_r', 'firma_t_e'],
            ] as $table => $columns
        ) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                try {
                    DB::statement('ALTER TABLE `'.$table.'` MODIFY `'.$column.'` VARCHAR(255) NULL');
                } catch (\Throwable) {
                    // keep legacy schema if alter fails
                }
            }
        }

        if (Schema::hasTable('orden_servicio_c') && Schema::hasColumn('orden_servicio_c', 'direccion')) {
            try {
                DB::statement('ALTER TABLE `orden_servicio_c` MODIFY `direccion` TEXT NULL');
            } catch (\Throwable) {
                // keep legacy schema if alter fails
            }
        }
    }
};
