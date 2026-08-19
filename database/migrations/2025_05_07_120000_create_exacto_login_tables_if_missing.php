<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tablas legacy `login` y `login_remember_tokens` para entornos nuevos (sqlite en tests)
     * y columnas opcionales para compatibilidad con Breeze (email_verified_at, remember_token).
     */
    public function up(): void
    {
        if (! Schema::hasTable('login')) {
            Schema::create('login', function (Blueprint $table) {
                $table->integer('id_tecnico');
                $table->primary('id_tecnico');
                $table->string('nombre_tecnico', 255);
                $table->string('nombre_tecnico_token', 64)->nullable();
                $table->string('nombre_usuario', 64)->nullable()->unique();
                $table->string('correo', 255);
                $table->string('contrasena', 255);
                $table->string('celular', 64)->nullable();
                $table->string('perfil', 50)->default('tecnico');
                $table->rememberToken();
                $table->timestamp('email_verified_at')->nullable();
                $table->unique('correo');
                $table->index('nombre_tecnico_token', 'idx_login_nombre_token');
            });
        } else {
            Schema::table('login', function (Blueprint $table) {
                if (! Schema::hasColumn('login', 'email_verified_at')) {
                    $table->timestamp('email_verified_at')->nullable();
                }
                if (! Schema::hasColumn('login', 'remember_token')) {
                    $table->rememberToken();
                }
            });
        }

        if (! Schema::hasTable('login_remember_tokens')) {
            Schema::create('login_remember_tokens', function (Blueprint $table) {
                $table->string('selector', 32)->primary();
                $table->string('token_hash', 64);
                $table->integer('id_tecnico');
                $table->dateTime('expires_at');
                $table->timestamp('created_at')->useCurrent();
                $table->index('id_tecnico', 'idx_remember_user');
                $table->index('expires_at', 'idx_remember_expires');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('login_remember_tokens');
        // No eliminar `login`: puede ser la tabla de producción compartida.
    }
};
