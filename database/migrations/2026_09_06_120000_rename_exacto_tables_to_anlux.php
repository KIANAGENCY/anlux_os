<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Renombra tablas legacy exacto_* → anlux_* en instalaciones existentes.
 * En instalaciones nuevas las migraciones ya crean anlux_*.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('exacto_settings') && ! Schema::hasTable('anlux_settings')) {
            Schema::rename('exacto_settings', 'anlux_settings');
        }

        if (Schema::hasTable('exacto_login_sequences') && ! Schema::hasTable('anlux_login_sequences')) {
            Schema::rename('exacto_login_sequences', 'anlux_login_sequences');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('anlux_settings') && ! Schema::hasTable('exacto_settings')) {
            Schema::rename('anlux_settings', 'exacto_settings');
        }

        if (Schema::hasTable('anlux_login_sequences') && ! Schema::hasTable('exacto_login_sequences')) {
            Schema::rename('anlux_login_sequences', 'exacto_login_sequences');
        }
    }
};
