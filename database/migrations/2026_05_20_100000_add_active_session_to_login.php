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

        Schema::table('login', function (Blueprint $table) {
            if (! Schema::hasColumn('login', 'active_session_id')) {
                $table->string('active_session_id', 128)->nullable();
            }
            if (! Schema::hasColumn('login', 'active_session_at')) {
                $table->dateTime('active_session_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('login')) {
            return;
        }

        Schema::table('login', function (Blueprint $table) {
            if (Schema::hasColumn('login', 'active_session_at')) {
                $table->dropColumn('active_session_at');
            }
            if (Schema::hasColumn('login', 'active_session_id')) {
                $table->dropColumn('active_session_id');
            }
        });
    }
};
