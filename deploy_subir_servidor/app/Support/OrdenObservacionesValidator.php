<?php

declare(strict_types=1);

namespace App\Support;

final class OrdenObservacionesValidator
{
    private const MESSAGE = 'Atención el campo observaciones se encuentra vacio, por favor indique si el cliente dejo un accesorio adicional en su equipo.';

    /** @var list<string> */
    private const KEYWORDS = [
        'accesorio', 'accesorios', 'cargador', 'funda', 'cable', 'bateria', 'batería',
        'mouse', 'teclado', 'maletin', 'maletín', 'estuche', 'sin accesorio',
        'no dejo', 'no dejó', 'solo equipo', 'ningun accesorio', 'ningún accesorio',
    ];

    /** @var list<string> */
    private const TRIVIAL = ['n/a', '-', 'ninguno', 'na'];

    /**
     * @param  mixed  $obsList
     */
    public static function validate($obsList): ?string
    {
        $textos = [];
        if (is_array($obsList)) {
            foreach ($obsList as $obs) {
                $t = trim((string) $obs);
                if ($t !== '') {
                    $textos[] = $t;
                }
            }
        }

        if ($textos === []) {
            return self::MESSAGE;
        }

        $combined = self::normalize(implode(' ', $textos));
        $hasKeyword = false;
        foreach (self::KEYWORDS as $keyword) {
            if (str_contains($combined, self::normalize($keyword))) {
                $hasKeyword = true;
                break;
            }
        }

        $allTrivial = true;
        foreach ($textos as $texto) {
            if (! in_array(self::normalize($texto), self::TRIVIAL, true)) {
                $allTrivial = false;
                break;
            }
        }

        if (! $hasKeyword || $allTrivial) {
            return self::MESSAGE;
        }

        return null;
    }

    private static function normalize(string $value): string
    {
        $lower = mb_strtolower(trim($value), 'UTF-8');

        return strtr($lower, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', 'ü' => 'u',
        ]);
    }
}
