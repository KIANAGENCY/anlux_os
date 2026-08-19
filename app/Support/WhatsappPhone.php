<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Normaliza teléfonos mexicanos al formato wa_id de Meta (521 + 10 dígitos).
 */
final class WhatsappPhone
{
    public static function normalize(mixed $phone): string
    {
        $digits = preg_replace('/\D+/', '', trim((string) $phone)) ?? '';
        if ($digits === '' || preg_match('/^(\d)\1+$/', $digits) === 1) {
            return '';
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        $country = (string) config('services.whatsapp.default_country_code', '52');

        if ($country === '52') {
            // +52 1 612 194 2057, 5216121942057, 526121942057
            if (preg_match('/^52(?:1)?(\d{10})$/', $digits, $m)) {
                return '521'.$m[1];
            }

            // Copiaron el móvil con el 1 nacional: 16121942057
            if (preg_match('/^1(\d{10})$/', $digits, $m)) {
                return '521'.$m[1];
            }

            // Solo 10 dígitos locales: 6121942057
            if (strlen($digits) === 10) {
                return '521'.$digits;
            }
        }

        if ($country !== '' && str_starts_with($digits, $country) && strlen($digits) >= strlen($country) + 8 && strlen($digits) <= 15) {
            return $digits;
        }

        if (strlen($digits) === 10) {
            return $country.$digits;
        }

        return (strlen($digits) >= 11 && strlen($digits) <= 15) ? $digits : '';
    }

    /**
     * Para guardar en orden_servicio_c: 10 dígitos locales sin lada.
     */
    public static function localMexicoDigits(string $digits): string
    {
        $digits = preg_replace('/\D+/', '', $digits) ?? '';
        if ($digits === '') {
            return '';
        }

        $canonical = self::normalize($digits);
        if (preg_match('/^521(\d{10})$/', $canonical, $m)) {
            return $m[1];
        }

        if (preg_match('/^\d{10}$/', $digits)) {
            return $digits;
        }

        if (strlen($digits) > 10) {
            $last10 = substr($digits, -10);
            if (preg_match('/^\d{10}$/', $last10) && self::normalize($last10) !== '') {
                return $last10;
            }
        }

        return $digits;
    }

    public static function formatDisplay(string $canonical): string
    {
        if (preg_match('/^521(\d{3})(\d{3})(\d{4})$/', $canonical, $m)) {
            return '+52 '.$m[1].' '.$m[2].' '.$m[3];
        }

        return $canonical;
    }
}
