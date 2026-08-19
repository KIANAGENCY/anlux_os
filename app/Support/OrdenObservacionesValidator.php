<?php

declare(strict_types=1);

namespace App\Support;

final class OrdenObservacionesValidator
{
    private const MESSAGE = 'Atención: el campo observaciones está vacío. Escriba al menos una observación.';

    /**
     * @param  mixed  $obsList
     */
    public static function validate($obsList): ?string
    {
        if (! is_array($obsList)) {
            return self::MESSAGE;
        }

        foreach ($obsList as $obs) {
            if (trim((string) $obs) !== '') {
                return null;
            }
        }

        return self::MESSAGE;
    }
}
