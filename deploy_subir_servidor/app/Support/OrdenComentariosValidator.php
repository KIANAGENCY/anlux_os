<?php

declare(strict_types=1);

namespace App\Support;

final class OrdenComentariosValidator
{
    private const MESSAGE = 'favor de rellenar el campo de comentarios, al menos de una descripción del trabajo realizado.';

    /** @var list<string> */
    private const WORK_KEYWORDS = [
        'trabajo', 'repar', 'revis', 'cambio', 'instal', 'limpieza', 'diagn',
        'falla', 'equipo', 'pieza', 'material',
    ];

    /** @var list<string> */
    private const TRIVIAL = ['n/a', '-', 'ninguno', 'na'];

    public static function validate(mixed $comentarios): ?string
    {
        $text = trim((string) $comentarios);
        if (mb_strlen($text, 'UTF-8') < 15) {
            return self::MESSAGE;
        }

        $normalized = self::normalize($text);
        if (in_array($normalized, self::TRIVIAL, true)) {
            return self::MESSAGE;
        }

        $hasWork = false;
        foreach (self::WORK_KEYWORDS as $keyword) {
            if (str_contains($normalized, $keyword)) {
                $hasWork = true;
                break;
            }
        }

        if (! $hasWork) {
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
