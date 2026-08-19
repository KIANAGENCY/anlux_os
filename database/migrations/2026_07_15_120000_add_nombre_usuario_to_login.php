<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('login')) {
            return;
        }

        if (! Schema::hasColumn('login', 'nombre_usuario')) {
            Schema::table('login', function (Blueprint $table) {
                $table->string('nombre_usuario', 64)->nullable()->after('nombre_tecnico_token');
                $table->unique('nombre_usuario', 'login_nombre_usuario_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('login') || ! Schema::hasColumn('login', 'nombre_usuario')) {
            return;
        }

        Schema::table('login', function (Blueprint $table) {
            $table->dropUnique('login_nombre_usuario_unique');
            $table->dropColumn('nombre_usuario');
        });
    }
};
