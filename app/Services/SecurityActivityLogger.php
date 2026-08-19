<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\ExactoAuthContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Schema;

final class SecurityActivityLogger
{
    public function log(string $eventType, string $severity, ?string $details = null): void
    {
        if (! Schema::hasTable('security_activity_log')) {
            return;
        }

        $user = ExactoAuthContext::currentUser();
        $usuario = null;
        if ($user !== null) {
            $usuario = trim((string) $user->nombre_tecnico);
            if ($usuario === '') {
                $usuario = 'Técnico #'.(int) $user->id_tecnico;
            }
        }

        DB::table('security_activity_log')->insert([
            'event_type' => $eventType,
            'severity' => $severity,
            'usuario' => $usuario,
            'ip' => Request::ip(),
            'uri' => substr((string) Request::getRequestUri(), 0, 255),
            'user_agent' => substr((string) Request::userAgent(), 0, 255),
            'details' => $details,
            'created_at' => now(),
        ]);
    }
}
