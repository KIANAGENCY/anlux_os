<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('login')) {
            Schema::table('login', function (Blueprint $table) {
                if (! Schema::hasColumn('login', 'activo')) {
                    $table->boolean('activo')->default(true);
                }
                if (! Schema::hasColumn('login', 'last_seen_at')) {
                    $table->dateTime('last_seen_at')->nullable();
                }
            });
        }

        if (! Schema::hasTable('impersonation_requests')) {
            Schema::create('impersonation_requests', function (Blueprint $table) {
                $table->id();
                $table->integer('admin_id');
                $table->integer('target_id');
                $table->string('status', 20)->default('pending');
                $table->string('token', 64)->unique();
                $table->integer('resolved_by_id')->nullable();
                $table->dateTime('created_at');
                $table->dateTime('expires_at');
                $table->dateTime('resolved_at')->nullable();
                $table->index(['status', 'expires_at'], 'idx_impersonation_status_expires');
                $table->index('admin_id', 'idx_impersonation_admin');
            });
        }

        if (! Schema::hasTable('orden_servicio_edit_locks')) {
            Schema::create('orden_servicio_edit_locks', function (Blueprint $table) {
                $table->integer('id_orden_c')->primary();
                $table->integer('locked_by_user_id');
                $table->string('locked_by_nombre', 255);
                $table->dateTime('locked_at');
                $table->dateTime('expires_at');
                $table->index('expires_at', 'idx_orden_edit_lock_expires');
                $table->index('locked_by_user_id', 'idx_orden_edit_lock_user');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('orden_servicio_edit_locks');
        Schema::dropIfExists('impersonation_requests');
        if (Schema::hasTable('login')) {
            Schema::table('login', function (Blueprint $table) {
                if (Schema::hasColumn('login', 'last_seen_at')) {
                    $table->dropColumn('last_seen_at');
                }
                if (Schema::hasColumn('login', 'activo')) {
                    $table->dropColumn('activo');
                }
            });
        }
    }
};
