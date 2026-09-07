<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Port de includes/telefono_vault.php para Laravel (misma semántica de cifrado y tokens).
 */
final class AnluxVaultService
{
    public function telefonoSecretBin(): ?string
    {
        $s = (string) config('anlux.telefono_secret', '');

        return $s !== '' ? hash('sha256', $s, true) : null;
    }

    public function loginCelularPepperBin(): string
    {
        $s = (string) config('anlux.telefono_secret', '');
        if ($s !== '') {
            return hash('sha256', $s, true);
        }
        $root = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string) config('anlux.pepper_root')), DIRECTORY_SEPARATOR);
        // Fallback histórico «exacto»: forma parte del material HMAC; no cambiar a la ligera.
        $db = (string) config('database.connections.mysql.database', 'exacto');

        return hash('sha256', 'exacto_login_celular_pepper_v2|'.$root.'|'.$db, true);
    }

    public function isSealed(?string $stored): bool
    {
        return is_string($stored) && strncmp($stored, 'v1:', 3) === 0;
    }

    public function normalizeDigits(?string $raw): string
    {
        return preg_replace('/\D+/', '', (string) $raw) ?? '';
    }

    public function revealString(?string $stored, bool $legacyPlainAsPhoneDigits): string
    {
        if ($stored === null || $stored === '') {
            return '';
        }
        if (! $this->isSealed($stored)) {
            return $legacyPlainAsPhoneDigits
                ? $this->normalizeDigits($stored)
                : trim((string) $stored);
        }
        $key = $this->telefonoSecretBin();
        if ($key === null) {
            return '';
        }
        $cipher = 'aes-256-gcm';
        $b64 = substr($stored, 3);
        $raw = base64_decode($b64, true);
        if ($raw === false) {
            return '';
        }
        $ivLen = openssl_cipher_iv_length($cipher);
        if ($ivLen === false) {
            return '';
        }
        $tagLen = 16;
        if (strlen($raw) < $ivLen + $tagLen + 1) {
            return '';
        }
        $iv = substr($raw, 0, $ivLen);
        $tag = substr($raw, $ivLen, $tagLen);
        $ct = substr($raw, $ivLen + $tagLen);
        $plain = openssl_decrypt($ct, $cipher, $key, OPENSSL_RAW_DATA, $iv, $tag, '');
        if ($plain === false) {
            return '';
        }

        return $legacyPlainAsPhoneDigits ? $this->normalizeDigits($plain) : trim($plain);
    }

    public function sealString(string $plaintext): string
    {
        $key = $this->telefonoSecretBin();
        if ($key === null || $plaintext === '') {
            return $plaintext;
        }
        $cipher = 'aes-256-gcm';
        if (! in_array($cipher, openssl_get_cipher_methods(), true)) {
            return $plaintext;
        }
        $ivLen = openssl_cipher_iv_length($cipher);
        if ($ivLen === false || $ivLen < 8) {
            return $plaintext;
        }
        $iv = random_bytes((int) $ivLen);
        $tag = '';
        $ct = openssl_encrypt($plaintext, $cipher, $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
        if ($ct === false || ! is_string($tag) || strlen($tag) !== 16) {
            return $plaintext;
        }

        return 'v1:'.base64_encode($iv.$tag.$ct);
    }

    public function telefonoReveal(?string $stored): string
    {
        return $this->revealString($stored, true);
    }

    public function direccionReveal(?string $stored): string
    {
        return $this->revealString($stored, false);
    }

    public function nombreClienteReveal(?string $stored): string
    {
        return $this->revealString($stored, false);
    }

    public function correoReveal(?string $stored): string
    {
        return $this->revealString($stored, false);
    }

    public function poblacionReveal(?string $stored): string
    {
        return $this->revealString($stored, false);
    }

    public function firmaRutaReveal(?string $stored): string
    {
        return $this->revealString($stored, false);
    }

    public function firmaRutaSeal(?string $ruta): ?string
    {
        if ($ruta === null) {
            return null;
        }
        $t = trim((string) $ruta);
        if ($t === '' || str_starts_with($t, 'data:image')) {
            return $t;
        }
        if ($this->telefonoSecretBin() === null) {
            return $t;
        }

        return $this->sealString($t);
    }

    public function telefonoSeal(string $digitsOnly): string
    {
        if ($digitsOnly === '') {
            return '';
        }
        if ($this->telefonoSecretBin() === null) {
            return $digitsOnly;
        }

        return $this->sealString($digitsOnly);
    }

    public function direccionSeal(?string $direccion): string
    {
        $t = trim((string) $direccion);
        if ($this->telefonoSecretBin() === null) {
            return $t;
        }

        return $t === '' ? '' : $this->sealString($t);
    }

    public function nombreClienteSeal(?string $nombre): string
    {
        $t = trim((string) $nombre);
        if ($this->telefonoSecretBin() === null) {
            return $t;
        }

        return $t === '' ? '' : $this->sealString($t);
    }

    public function correoSeal(?string $correo): string
    {
        $t = trim((string) $correo);
        if ($this->telefonoSecretBin() === null) {
            return $t;
        }

        return $t === '' ? '' : $this->sealString($t);
    }

    public function poblacionSeal(?string $poblacion): string
    {
        $t = trim((string) $poblacion);
        if ($this->telefonoSecretBin() === null) {
            return $t;
        }

        return $t === '' ? '' : $this->sealString($t);
    }

    public function nombreClienteNormalizeSearch(?string $value): string
    {
        $text = mb_strtolower(trim((string) $value), 'UTF-8');
        $text = strtr($text, [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n',
        ]);
        $text = preg_replace('/[^a-z0-9]+/u', ' ', $text) ?? '';
        $text = preg_replace('/\s+/', ' ', $text) ?? '';

        return trim($text);
    }

    /** @return list<string> */
    public function nombreClienteSearchParts(?string $value): array
    {
        $normalized = $this->nombreClienteNormalizeSearch($value);
        if ($normalized === '') {
            return [];
        }

        return array_values(array_unique(array_filter(explode(' ', $normalized), static function ($part): bool {
            return strlen((string) $part) >= 2;
        })));
    }

    public function nombreClienteSearchToken(string $part): string
    {
        return hash_hmac('sha256', 'orden_nombre_busqueda_v1|'.$part, $this->loginCelularPepperBin(), false);
    }

    public function ordenSearchToken(string $type, string $value): string
    {
        return hash_hmac('sha256', 'orden_busqueda_v2|'.$type.'|'.$value, $this->loginCelularPepperBin(), false);
    }

    public function tecnicoNombreNormalize(?string $nombre): string
    {
        $text = mb_strtolower(trim((string) $nombre), 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text) ?? '';

        return trim($text);
    }

    /** Igual que anlux_tecnico_nombre_token() en legacy. */
    public function tecnicoNombreToken(?string $nombre): string
    {
        return $this->ordenSearchToken('login_nombre_tecnico', $this->tecnicoNombreNormalize($nombre));
    }

    public function tecnicoNombreSeal(?string $nombre): string
    {
        $t = trim((string) $nombre);
        if ($this->telefonoSecretBin() === null) {
            return $t;
        }

        return $t === '' ? '' : $this->sealString($t);
    }

    public function tecnicoNombreReveal(?string $stored): string
    {
        return $this->revealString($stored, false);
    }

    /** GROUP_CONCAT de orden_servicio_tecnico_log (separador « · »). */
    public function tecnicosLogConcatReveal(?string $concat): string
    {
        $concat = trim((string) $concat);
        if ($concat === '') {
            return '';
        }

        $revealed = [];
        foreach (explode(' · ', $concat) as $part) {
            $name = $this->tecnicoNombreReveal($part);
            if ($name !== '') {
                $revealed[] = $name;
            }
        }

        return implode(' · ', $revealed);
    }

    /** Hash irreversible para login.celular (mismo algoritmo que legacy). */
    public function loginCelularHashStore(string $digitsOnly): string
    {
        return hash_hmac('sha256', $digitsOnly, $this->loginCelularPepperBin(), false);
    }

    /** @return list<string> */
    public function nombreClienteQueryTokens(?string $search): array
    {
        return array_map(fn (string $part): string => $this->ordenSearchToken('nombre', $part), $this->nombreClienteSearchParts($search));
    }

    public function correoClienteNormalizeSearch(?string $correo): string
    {
        return mb_strtolower(trim((string) $correo), 'UTF-8');
    }

    /** @return list<string> */
    public function correoClienteQueryTokens(?string $search): array
    {
        $normalized = $this->correoClienteNormalizeSearch($search);
        if ($normalized === '' || ! str_contains($normalized, '@')) {
            return [];
        }

        return [$this->ordenSearchToken('correo', $normalized)];
    }

    /** @return list<string> */
    public function telefonoClienteQueryTokens(?string $search): array
    {
        $digits = $this->normalizeDigits($search);
        if (strlen($digits) < 4) {
            return [];
        }

        return [$this->ordenSearchToken('telefono', $digits)];
    }

    /** @return array{sql: string, params: array<int, string>} */
    public function buildSearchWhere(string $search): array
    {
        $search = trim($search);
        if ($search === '') {
            return ['sql' => '', 'params' => []];
        }
        $nameTokens = $this->nombreClienteQueryTokens($search);
        $phoneTokens = $this->telefonoClienteQueryTokens($search);
        $emailTokens = $this->correoClienteQueryTokens($search);
        $folioLike = '%'.$search.'%';
        $searchParts = ['c.folio LIKE ?'];
        $searchParams = [$folioLike];
        if ($nameTokens !== []) {
            $nameParts = [];
            foreach ($nameTokens as $token) {
                $nameParts[] = 'EXISTS (SELECT 1 FROM orden_servicio_nombre_busqueda nb WHERE nb.id_orden_c = c.id_orden_c AND nb.token = ?)';
                $searchParams[] = $token;
            }
            $searchParts[] = '('.implode(' AND ', $nameParts).')';
        }
        foreach (array_merge($phoneTokens, $emailTokens) as $token) {
            $searchParts[] = 'EXISTS (SELECT 1 FROM orden_servicio_nombre_busqueda nb WHERE nb.id_orden_c = c.id_orden_c AND nb.token = ?)';
            $searchParams[] = $token;
        }
        if (count($searchParts) > 1 || $folioLike !== '%%') {
            return ['sql' => ' AND ('.implode(' OR ', $searchParts).')', 'params' => $searchParams];
        }

        return ['sql' => '', 'params' => []];
    }

    /** @return list<string> */
    public function ordenClienteSearchTokens(?string $nombrePlano, ?string $telefonoPlano = null, ?string $correoPlano = null): array
    {
        $nombreTokens = [];
        foreach ($this->nombreClienteSearchParts($nombrePlano) as $part) {
            $len = strlen($part);
            for ($i = 2; $i <= $len; $i++) {
                $prefix = substr($part, 0, $i);
                $nombreTokens[] = $this->nombreClienteSearchToken($prefix);
                $nombreTokens[] = $this->ordenSearchToken('nombre', $prefix);
            }
        }
        $telTokens = [];
        $digits = $this->normalizeDigits($telefonoPlano);
        $len = strlen($digits);
        for ($i = 4; $i <= $len; $i++) {
            $telTokens[] = $this->ordenSearchToken('telefono', substr($digits, 0, $i));
        }
        $correoNorm = $this->correoClienteNormalizeSearch($correoPlano);
        $emailTokens = $correoNorm !== '' ? [$this->ordenSearchToken('correo', $correoNorm)] : [];

        return array_values(array_unique(array_merge($nombreTokens, $telTokens, $emailTokens)));
    }
}
