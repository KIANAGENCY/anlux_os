<?php

declare(strict_types=1);

namespace App\Support;

final class TipoServicioCatalog
{
    /** @return list<string> */
    public static function values(): array
    {
        return [
            '1. Mantenimiento',
            '2. Reparación',
            '3. Instalación',
            '4. Garantía',
            '5. Revisión',
        ];
    }
}
