<?php

declare(strict_types=1);

namespace App\Support;

final class OrderStatus
{
    public static function map(string $value): string
    {
        $key = mb_strtolower(trim($value), 'UTF-8');
        // BD/legado a veces guarda "Enproceso" sin espacio; el canónico es "En proceso".
        $keyCompact = preg_replace('/\s+/u', '', $key) ?? $key;
        $map = [
            'rojo' => 'Recepción',
            'recepción' => 'Recepción',
            'recepcion' => 'Recepción',
            'naranja' => 'En proceso',
            'en proceso' => 'En proceso',
            'enproceso' => 'En proceso',
            'proceso' => 'En proceso',
            'amarillo' => 'Terminado',
            'terminado' => 'Terminado',
            'verde' => 'Entregado',
            'entregado' => 'Entregado',
        ];

        return $map[$key] ?? $map[$keyCompact] ?? $value;
    }

    public static function isEnProceso(?string $estatus): bool
    {
        return self::map((string) $estatus) === 'En proceso';
    }

    public static function isEntregado(?string $estatus): bool
    {
        return mb_stripos(trim((string) $estatus), 'entregado') !== false;
    }

    public static function displayLabel(string $estatus): string
    {
        $normalized = mb_strtolower(trim($estatus), 'UTF-8');

        if ($normalized === '') {
            return '';
        }

        if (str_contains($normalized, 'recepc')) {
            return 'Recepción';
        }
        if (str_contains($normalized, 'proceso')) {
            return 'En proceso';
        }
        if (str_contains($normalized, 'termin')) {
            return 'Terminado';
        }
        if (str_contains($normalized, 'entreg')) {
            return 'Entregado';
        }

        return self::map($estatus);
    }
}
