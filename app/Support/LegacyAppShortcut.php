<?php

declare(strict_types=1);

namespace App\Support;

final class LegacyAppShortcut
{
    /** @return list<array{href: string, label: string}> */
    public static function escapeRoutes(): array
    {
        $base = self::normalizedBase();
        if ($base === null) {
            return [];
        }

        return [
            ['href' => $base.'/ordenes.php', 'label' => 'O'.mb_chr(0x00D3, 'UTF-8').'rdenes registradas'],
            ['href' => $base.'/orden_servicio.php', 'label' => 'Nueva orden de servicio'],
            ['href' => $base.'/historial_ordenes.php', 'label' => 'Historial PDF'],
            ['href' => $base.'/admin_destino.php', 'label' => 'Panel administración'],
            ['href' => $base.'/login.php', 'label' => 'Inicio de sesión (legacy)'],
        ];
    }

    private static function normalizedBase(): ?string
    {
        $u = trim((string) config('exacto.legacy_url'));
        if ($u === '') {
            return null;
        }

        return rtrim($u, '/');
    }
}
