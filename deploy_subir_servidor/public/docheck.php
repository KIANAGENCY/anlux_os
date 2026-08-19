<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

/**
 * Diagnóstico WhatsApp Cloud API (red, token, Phone Number ID, cola, búsqueda por teléfono).
 *
 * Uso:
 *   docheck.php?key=exacto99
 *   docheck.php?key=exacto99&wa_test_meta=1
 *   docheck.php?key=exacto99&wa_cola=1
 *   docheck.php?key=exacto99&wa_status=7
 *   docheck.php?key=exacto99&wa_phone=6122885758
 *   docheck.php?key=exacto99&wa_compare=1138876072642282,1537852524373963
 *   docheck.php?key=exacto99&wa_discover=1
 *   docheck.php?key=exacto99&wa_run_queue=1
 *   docheck.php?key=exacto99&wa_inbound=1
 *   docheck.php?key=exacto99&wa_inbound=1&wa_phone=6121684390
 *
 * BORRAR en producción cuando termines.
 */

const DOCHECK_KEY = 'exacto99';

/** IDs que NO son Phone Number ID (confusión frecuente en Meta). */
const WA_KNOWN_WRONG_IDS = [
    '1537852524373963' => 'App ID «CELULAR SOPORTE» — no usar en WHATSAPP_PHONE_NUMBER_ID',
    '1964158600503621' => 'Business / WABA u otro objeto — no es línea de envío',
    '61590273249931' => 'Objeto interno sin display_phone_number',
];

if (($_GET['key'] ?? '') !== DOCHECK_KEY) {
    http_response_code(403);
    exit('Forbidden - usa ?key='.DOCHECK_KEY);
}

header('Content-Type: text/plain; charset=utf-8');

$root = dirname(__DIR__);
$runGraphApi = ($_GET['wa_test_meta'] ?? '') === '1';
$waDiscover = ($_GET['wa_discover'] ?? '') === '1';
$waCompareRaw = trim((string) ($_GET['wa_compare'] ?? ''));
$waPhoneSearch = preg_replace('/\D+/', '', trim((string) ($_GET['wa_phone'] ?? ''))) ?? '';
$waStatusId = (int) ($_GET['wa_status'] ?? 0);
$waInbound = ($_GET['wa_inbound'] ?? '') === '1';
$waCola = ($_GET['wa_cola'] ?? '') === '1'
    || ($waStatusId === 0 && $waPhoneSearch === '' && $waCompareRaw === '' && ! $waDiscover && ! $waInbound);

$waReady = false;
/** @var array<string, mixed>|null */
$r2 = null;
/** @var array<string, mixed>|null */
$phoneMeta = null;
$f = ['ok' => false, 'ms' => 0.0, 'error' => 'no ejecutado', 'ip' => ''];

echo "=== EXACTO - Diagnostico WhatsApp (red + API + cola) ===\n\n";
echo 'Fecha: '.date('Y-m-d H:i:s')."\n";
echo 'PHP: '.PHP_VERSION."\n";
echo 'SAPI: '.PHP_SAPI."\n";
echo 'Servidor: '.(gethostname() ?: '?')."\n\n";

$line = static function (string $label, string $value = ''): void {
    echo $value === '' ? $label."\n" : $label.': '.$value."\n";
};

$verdict = static function (bool $ok, string $okMsg, string $failMsg): void {
    echo ($ok ? '[OK] ' : '[FALLO] ').($ok ? $okMsg : $failMsg)."\n";
};

$maskSecret = static function (string $value): string {
    $value = trim($value);
    if ($value === '') {
        return '(vacio)';
    }

    return substr($value, 0, 6).'...('.strlen($value).' chars)';
};

$interpretWaStatus = static function (string $status): string {
    return match ($status) {
        'queued' => 'EN COLA — el cron aún no envió a Meta (o el job sigue en tabla jobs).',
        'accepted' => 'SALIO DE COLA — Meta aceptó el mensaje (wamid). Debería llegar al celular en breve.',
        'delivered' => 'ENTREGADO al dispositivo del cliente.',
        'read' => 'LEIDO por el cliente.',
        'failed' => 'FALLO al enviar (revisar message; si timeout = bloqueo graph.facebook.com).',
        'duplicate' => 'No se reenvió: ya había envío para este estatus.',
        default => 'Estado intermedio.',
    };
};

$interpretMetaError = static function (int $code, string $message): string {
    if ($code === 131030 || str_contains(strtolower($message), 'not in allowed list')) {
        return '131030 — El destinatario NO está en la lista de números de prueba de Meta. '
            .'Agrega +52XXXXXXXXXX en developers.facebook.com → WhatsApp → API Setup → Manage phone number list.';
    }
    if ($code === 190 || str_contains(strtolower($message), 'access token')) {
        return '190 — Token inválido o expirado. Renueva WHATSAPP_ACCESS_TOKEN.';
    }
    if (str_contains(strtolower($message), 'does not exist')) {
        return 'WHATSAPP_PHONE_NUMBER_ID incorrecto o token sin permiso sobre ese número.';
    }

    return $message !== '' ? $message : 'Revisa JSON de error en Meta.';
};

/**
 * @return array{ok: bool, ms: float, error: string, ip: string}
 */
$tcp443 = static function (string $host, int $timeoutSec = 10): array {
    $start = microtime(true);
    $errno = 0;
    $errstr = '';
    $ip = '';

    $records = @dns_get_record($host, DNS_A);
    if (is_array($records) && $records !== []) {
        $ip = (string) ($records[0]['ip'] ?? '');
    }

    $fp = @fsockopen('ssl://'.$host, 443, $errno, $errstr, $timeoutSec);
    $ms = round((microtime(true) - $start) * 1000, 1);

    if ($fp !== false) {
        if (is_resource($fp)) {
            fclose($fp);
        } elseif (is_object($fp) && method_exists($fp, 'close')) {
            $fp->close();
        }

        return ['ok' => true, 'ms' => $ms, 'error' => '', 'ip' => $ip];
    }

    return ['ok' => false, 'ms' => $ms, 'error' => "errno={$errno} {$errstr}", 'ip' => $ip];
};

/**
 * @return array{
 *   ok: bool,
 *   http_code: int,
 *   total_ms: float,
 *   connect_ms: float,
 *   primary_ip: string,
 *   error: string,
 *   body_snip: string,
 *   body_json: array<string, mixed>|null
 * }
 */
$curlGet = static function (string $url, array $headers = [], int $connectTimeout = 15, int $timeout = 25): array {
    if (! function_exists('curl_init')) {
        return [
            'ok' => false,
            'http_code' => 0,
            'total_ms' => 0.0,
            'connect_ms' => 0.0,
            'primary_ip' => '',
            'error' => 'extension curl no disponible en PHP',
            'body_snip' => '',
            'body_json' => null,
        ];
    }

    $ch = curl_init($url);
    if ($ch === false) {
        return [
            'ok' => false,
            'http_code' => 0,
            'total_ms' => 0.0,
            'connect_ms' => 0.0,
            'primary_ip' => '',
            'error' => 'curl_init fallo',
            'body_snip' => '',
            'body_json' => null,
        ];
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_CONNECTTIMEOUT => $connectTimeout,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_USERAGENT => 'ExactoDocheck/2.0',
    ]);

    $body = curl_exec($ch);
    $errno = curl_errno($ch);
    $err = curl_error($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);

    $httpCode = (int) ($info['http_code'] ?? 0);
    $connectMs = round(((float) ($info['connect_time'] ?? 0)) * 1000, 1);
    $totalMs = round(((float) ($info['total_time'] ?? 0)) * 1000, 1);
    $primaryIp = (string) ($info['primary_ip'] ?? '');
    $bodyStr = (string) $body;
    $decoded = json_decode($bodyStr, true);

    if ($errno !== 0) {
        return [
            'ok' => false,
            'http_code' => $httpCode,
            'total_ms' => $totalMs,
            'connect_ms' => $connectMs,
            'primary_ip' => $primaryIp,
            'error' => "cURL #{$errno}: {$err}",
            'body_snip' => '',
            'body_json' => null,
        ];
    }

    return [
        'ok' => $httpCode >= 200 && $httpCode < 500,
        'http_code' => $httpCode,
        'total_ms' => $totalMs,
        'connect_ms' => $connectMs,
        'primary_ip' => $primaryIp,
        'error' => '',
        'body_snip' => substr($bodyStr, 0, 300),
        'body_json' => is_array($decoded) ? $decoded : null,
    ];
};

/**
 * @return array{display_phone_number: string, verified_name: string, id: string, is_test_line: bool}|null
 */
$parsePhoneNode = static function (?array $json): ?array {
    if ($json === null) {
        return null;
    }
    $display = trim((string) ($json['display_phone_number'] ?? ''));
    if ($display === '') {
        return null;
    }

    return [
        'display_phone_number' => $display,
        'verified_name' => trim((string) ($json['verified_name'] ?? '')),
        'id' => trim((string) ($json['id'] ?? '')),
        'is_test_line' => str_contains($display, '555-650') || str_contains(strtolower((string) ($json['verified_name'] ?? '')), 'test'),
    ];
};

echo "=== 1. PHP (salida HTTPS) ===\n";
$verdict(function_exists('curl_init'), 'curl_init disponible', 'Falta extension curl');
$verdict(extension_loaded('openssl'), 'openssl cargado', 'Falta openssl - TLS 443 fallara');
$line('allow_url_fopen', ini_get('allow_url_fopen') ? 'On' : 'Off');
$disabled = array_filter(array_map('trim', explode(',', (string) ini_get('disable_functions'))));
$blocked = array_intersect(['curl_exec', 'fsockopen', 'stream_socket_client'], $disabled);
if ($blocked !== []) {
    echo '[FALLO] Funciones deshabilitadas: '.implode(', ', $blocked)."\n";
} else {
    echo "[OK] curl_exec / fsockopen permitidos\n";
}

echo "\n=== 2. DNS ===\n";
$graphHost = 'graph.facebook.com';
$dns = @dns_get_record($graphHost, DNS_A);
if (! is_array($dns) || $dns === []) {
    echo "[FALLO] No se resolvio {$graphHost}\n";
} else {
    echo "[OK] {$graphHost} resuelve a:\n";
    foreach ($dns as $r) {
        if (isset($r['ip'])) {
            echo "  A    {$r['ip']}\n";
        }
    }
}

echo "\n=== 3. Conexion TCP+TLS puerto 443 ===\n";
$controlHost = 'www.google.com';
echo "--- Control -> {$controlHost} ---\n";
$g = $tcp443($controlHost, 10);
if ($g['ok']) {
    echo "[OK] TLS 443 a {$controlHost} en {$g['ms']} ms\n";
} else {
    echo "[FALLO] {$g['error']} ({$g['ms']} ms)\n";
}

echo "--- Objetivo -> {$graphHost}:443 ---\n";
$f = $tcp443($graphHost, 15);
if ($f['ok']) {
    echo "[OK] TLS 443 a {$graphHost} en {$f['ms']} ms".($f['ip'] !== '' ? " (IP: {$f['ip']})" : '')."\n";
} else {
    echo "[FALLO] NO llega TLS a {$graphHost}:443 - {$f['error']} ({$f['ms']} ms)\n";
    if ($g['ok']) {
        echo "  -> Google OK pero Facebook NO = bloqueo selectivo a Meta.\n";
    }
}

echo "\n=== 4. HTTP GET (cURL) sin token ===\n";
$r = $curlGet('https://graph.facebook.com/', [], 15, 20);
if ($r['error'] !== '') {
    echo "[FALLO] {$r['error']}\n";
    echo "  connect_ms={$r['connect_ms']} total_ms={$r['total_ms']} ip={$r['primary_ip']}\n";
} else {
    echo "[OK] HTTP {$r['http_code']} en {$r['total_ms']} ms\n";
}

if (! is_file($root.'/vendor/autoload.php')) {
    echo "\n[FALTA] vendor/autoload.php - no se prueba API con token.\n";
    goto summary;
}

try {
    require $root.'/vendor/autoload.php';
    $app = require $root.'/bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
} catch (Throwable $e) {
    echo "\n[FALLO BOOTSTRAP] ".$e->getMessage()."\n";
    goto summary;
}

echo "\n=== 5. Config WhatsApp (.env) ===\n";
$waEnabled = filter_var(config('services.whatsapp.enabled', false), FILTER_VALIDATE_BOOL);
$waPhoneId = trim((string) config('services.whatsapp.phone_number_id', ''));
$waToken = trim((string) config('services.whatsapp.access_token', ''));
$base = rtrim((string) config('services.whatsapp.base_url', 'https://graph.facebook.com'), '/');
$version = trim((string) config('services.whatsapp.graph_version', 'v20.0'));
$connectTimeout = max(10, (int) config('services.whatsapp.http_connect_timeout', 60));
$timeout = max(30, (int) config('services.whatsapp.http_timeout', 180));
$countryCode = trim((string) config('services.whatsapp.default_country_code', '52'));

$line('APP_URL', (string) config('app.url'));
$line('WHATSAPP_CLOUD_ENABLED', $waEnabled ? 'true' : 'false');
$line('PHONE_NUMBER_ID', $waPhoneId !== '' ? $waPhoneId : '(vacio)');
$line('ACCESS_TOKEN', $maskSecret($waToken));
$line('WHATSAPP_DEFAULT_COUNTRY_CODE', $countryCode !== '' ? $countryCode : '52');
$line('QUEUE_CONNECTION', (string) config('queue.default'));

if ($waPhoneId !== '' && isset(WA_KNOWN_WRONG_IDS[$waPhoneId])) {
    echo "\n[ATENCION] PHONE_NUMBER_ID en .env parece INCORRECTO:\n";
    echo '  '.WA_KNOWN_WRONG_IDS[$waPhoneId]."\n";
    echo "  Usa el ID que tenga display_phone_number (ver seccion 6 o wa_compare=).\n";
}

echo "\nPlantillas configuradas:\n";
foreach (['recepcion', 'terminado', 'entregado'] as $tplKey) {
    $tplName = trim((string) config('services.whatsapp.templates.'.$tplKey, ''));
    echo '  '.$tplKey.': '.($tplName !== '' ? $tplName : '(vacio)')."\n";
}

$waReady = $waEnabled && $waPhoneId !== '' && $waToken !== '';
echo "\nConfig lista para API? ".($waReady ? '[SI]' : '[NO]')."\n";

echo "\n=== 6. Graph API — Phone Number ID en .env ===\n";
if (! $waReady) {
    echo "[OMITIDO] Falta enabled, PHONE_NUMBER_ID o ACCESS_TOKEN.\n";
} else {
    $graphApiUrl = $base.'/'.$version.'/'.$waPhoneId.'?fields=id,display_phone_number,verified_name,quality_rating,code_verification_status';
    echo "URL: {$graphApiUrl}\n\n";

    echo "--- 6a. cURL directo ---\n";
    $authHeaders = ['Authorization: Bearer '.$waToken, 'Accept: application/json'];
    $r2 = $curlGet($graphApiUrl, $authHeaders, min(15, $connectTimeout), min(30, $timeout));
    if ($r2['error'] !== '') {
        echo "[FALLO] {$r2['error']}\n";
    } elseif ($r2['http_code'] === 200) {
        echo "[OK] HTTP 200 - red y token OK\n";
        $phoneMeta = $parsePhoneNode($r2['body_json']);
        if ($phoneMeta !== null) {
            echo '  Linea emisora: '.$phoneMeta['display_phone_number']."\n";
            echo '  Nombre verificado: '.$phoneMeta['verified_name']."\n";
            echo '  ID confirmado: '.$phoneMeta['id']."\n";
            if ($phoneMeta['is_test_line']) {
                echo "\n  [MODO PRUEBA META] Linea +1 555-650-xxxx (Test Number).\n";
                echo "  Solo puedes enviar a numeros agregados en Meta → WhatsApp → API Setup\n";
                echo "  → «Manage phone number list» (error 131030 si falta el destinatario).\n";
            }
        } else {
            echo "  [ATENCION] HTTP 200 pero sin display_phone_number — revisa si el ID es App ID u otro objeto.\n";
            if ($r2['body_snip'] !== '') {
                echo '  '.str_replace("\n", ' ', $r2['body_snip'])."\n";
            }
        }
    } elseif (in_array($r2['http_code'], [401, 403], true)) {
        echo "[RED OK] HTTP {$r2['http_code']} - llega a Meta; token o permiso incorrecto\n";
        if ($r2['body_snip'] !== '') {
            echo '  Meta: '.str_replace("\n", ' ', $r2['body_snip'])."\n";
        }
    } else {
        echo "[API] HTTP {$r2['http_code']} - red OK pero Meta rechazo la peticion\n";
        if ($r2['body_snip'] !== '') {
            echo '  Meta: '.str_replace("\n", ' ', $r2['body_snip'])."\n";
        }
        echo "  -> Revisa Phone Number ID, token y permiso whatsapp_business_messaging\n";
    }

    if ($runGraphApi || ($r2['error'] ?? '') !== '' || ($r2['http_code'] ?? 0) !== 200) {
        echo "\n--- 6b. Laravel Http ---\n";
        try {
            $t0 = microtime(true);
            $response = Http::acceptJson()
                ->withToken($waToken)
                ->connectTimeout($connectTimeout)
                ->timeout($timeout)
                ->get($base.'/'.$version.'/'.$waPhoneId, [
                    'fields' => 'id,display_phone_number,verified_name,quality_rating',
                ]);
            $elapsed = round((microtime(true) - $t0) * 1000, 1);
            echo 'HTTP '.$response->status()." ({$elapsed} ms)\n";
            if ($response->successful()) {
                echo "[OK] Meta respondio via Laravel Http\n";
            } else {
                echo substr((string) $response->body(), 0, 400)."\n";
            }
        } catch (Throwable $e) {
            echo '[FALLO Http] '.$e->getMessage()."\n";
        }
    }
}

if ($waCompareRaw !== '' && $waReady) {
    echo "\n=== 6c. Comparar Phone Number IDs ===\n";
    $ids = array_values(array_filter(array_map(
        static fn (string $p) => preg_replace('/\D+/', '', trim($p)) ?? '',
        explode(',', $waCompareRaw)
    )));
    if ($ids === []) {
        echo "(sin IDs validos en wa_compare=)\n";
    } else {
        $authHeaders = ['Authorization: Bearer '.$waToken, 'Accept: application/json'];
        foreach ($ids as $testId) {
            echo str_repeat('-', 50)."\n";
            echo "ID: {$testId}\n";
            if (isset(WA_KNOWN_WRONG_IDS[$testId])) {
                echo '[AVISO] '.WA_KNOWN_WRONG_IDS[$testId]."\n";
            }
            $url = $base.'/'.$version.'/'.$testId.'?fields=id,display_phone_number,verified_name';
            $cr = $curlGet($url, $authHeaders, min(15, $connectTimeout), min(30, $timeout));
            if ($cr['error'] !== '') {
                echo "[FALLO] {$cr['error']}\n";
                continue;
            }
            echo 'HTTP '.$cr['http_code']."\n";
            $parsed = $parsePhoneNode($cr['body_json']);
            if ($parsed !== null) {
                echo "[USAR PARA ENVIO] display_phone_number={$parsed['display_phone_number']}\n";
                echo "  verified_name={$parsed['verified_name']}\n";
                echo "  WHATSAPP_PHONE_NUMBER_ID={$parsed['id']}\n";
                if ($parsed['is_test_line']) {
                    echo "  (linea de prueba Meta — destinatarios deben estar en lista de prueba)\n";
                }
            } elseif (is_array($cr['body_json']['error'] ?? null)) {
                $err = $cr['body_json']['error'];
                $code = (int) ($err['code'] ?? 0);
                $msg = (string) ($err['message'] ?? '');
                echo '[NO ES PHONE NUMBER ID] '.$interpretMetaError($code, $msg)."\n";
            } else {
                echo "[NO] Sin display_phone_number — no usar en WHATSAPP_PHONE_NUMBER_ID\n";
            }
        }
        echo str_repeat('-', 50)."\n";
    }
}

if ($waDiscover && $waReady) {
    echo "\n=== 6d. Descubrir token y WABA (resumen) ===\n";
    try {
        $debug = Http::acceptJson()
            ->withToken($waToken)
            ->connectTimeout($connectTimeout)
            ->timeout($timeout)
            ->get($base.'/'.$version.'/debug_token', [
                'input_token' => $waToken,
                'access_token' => $waToken,
            ]);
        echo 'debug_token HTTP '.$debug->status()."\n";
        $data = $debug->json('data');
        if (is_array($data)) {
            echo '  app_id: '.($data['app_id'] ?? '?')."\n";
            echo '  is_valid: '.(isset($data['is_valid']) ? ($data['is_valid'] ? 'true' : 'false') : '?')."\n";
            $scopes = is_array($data['scopes'] ?? null) ? $data['scopes'] : [];
            foreach (['whatsapp_business_messaging', 'whatsapp_business_management'] as $scope) {
                $ok = in_array($scope, $scopes, true);
                echo '  '.($ok ? '[OK]' : '[FALTA]').' '.$scope."\n";
            }
            $granular = $data['granular_scopes'] ?? [];
            if (is_array($granular) && $granular !== []) {
                echo "  WABA / target_ids del token:\n";
                foreach ($granular as $g) {
                    if (! is_array($g)) {
                        continue;
                    }
                    $ids = $g['target_ids'] ?? [];
                    echo '    '.($g['scope'] ?? '?').': '.(is_array($ids) ? implode(', ', $ids) : '')."\n";
                }
                echo "  Prueba: docheck.php?key=".DOCHECK_KEY."&wa_compare=WABA_ID/phone_id,...\n";
            }
        }
        echo "\n  Descubrimiento completo: wa_test_send.php?key=".DOCHECK_KEY."&mode=discover\n";
    } catch (Throwable $e) {
        echo '[FALLO] '.$e->getMessage()."\n";
    }
}

if ($waPhoneSearch !== '') {
    echo "\n=== 7. Buscar mensajes al telefono {$waPhoneSearch} ===\n";
    $patterns = array_values(array_unique([
        $waPhoneSearch,
        $countryCode.$waPhoneSearch,
        '52'.$waPhoneSearch,
    ]));
    echo 'Patrones: '.implode(', ', $patterns)."\n\n";

    try {
        if (Schema::hasTable('order_whatsapp_notifications')) {
            echo "--- order_whatsapp_notifications ---\n";
            $notifs = DB::table('order_whatsapp_notifications')
                ->where(function ($q) use ($patterns) {
                    foreach ($patterns as $p) {
                        if ($p !== '') {
                            $q->orWhere('telefono', 'like', '%'.$p.'%');
                        }
                    }
                })
                ->orderByDesc('id')
                ->limit(15)
                ->get();
            if ($notifs->isEmpty()) {
                echo "(sin registros para este numero)\n";
                echo "  -> wa_test_send.php NO guarda en BD; solo ordenes automaticas.\n";
            } else {
                foreach ($notifs as $n) {
                    $st = (string) ($n->status ?? '');
                    echo sprintf(
                        "  #%d orden=%d %s tel=%s status=%s\n      -> %s\n",
                        (int) $n->id,
                        (int) $n->id_orden_c,
                        (string) ($n->folio ?? ''),
                        (string) ($n->telefono ?? ''),
                        $st,
                        $interpretWaStatus($st)
                    );
                    if (trim((string) ($n->message ?? '')) !== '') {
                        echo '      msg: '.substr((string) $n->message, 0, 120)."\n";
                    }
                }
            }
        }

        if (Schema::hasTable('wa_messages')) {
            echo "\n--- wa_messages (chat soporte) ---\n";
            $msgs = DB::table('wa_messages')
                ->where(function ($q) use ($patterns) {
                    foreach ($patterns as $p) {
                        if ($p !== '') {
                            $q->orWhere('wa_phone', 'like', '%'.$p.'%');
                        }
                    }
                })
                ->orderByDesc('id')
                ->limit(15)
                ->get();
            if ($msgs->isEmpty()) {
                echo "(sin mensajes de chat para este numero)\n";
            } else {
                foreach ($msgs as $m) {
                    echo sprintf(
                        "  #%d %s %s status=%s %s\n",
                        (int) $m->id,
                        (string) ($m->direction ?? ''),
                        (string) ($m->wa_phone ?? ''),
                        (string) ($m->status ?? ''),
                        substr((string) ($m->body ?? ''), 0, 60)
                    );
                }
            }
        }
    } catch (Throwable $e) {
        echo '[AVISO] '.$e->getMessage()."\n";
    }
}

try {
    if ($waCola && Schema::hasTable('order_whatsapp_notifications')) {
        echo "\n=== ".($waPhoneSearch !== '' ? '8' : '7').". Cola WhatsApp (jobs + notificaciones) ===\n";

        if (Schema::hasTable('jobs')) {
            $pendingJobs = (int) DB::table('jobs')->count();
            $waJobs = DB::table('jobs')
                ->where('payload', 'like', '%SendOrderWhatsappJob%')
                ->orderByDesc('id')
                ->limit(5)
                ->get(['id', 'queue', 'attempts', 'reserved_at', 'available_at', 'created_at']);

            echo "jobs pendientes (total): {$pendingJobs}\n";
            if ($waJobs->isEmpty()) {
                echo "SendOrderWhatsappJob en cola: (ninguno) — ya se procesaron o nunca se encolaron.\n";
            } else {
                echo "SendOrderWhatsappJob AUN EN COLA:\n";
                foreach ($waJobs as $j) {
                    $reserved = $j->reserved_at ? date('Y-m-d H:i:s', (int) $j->reserved_at) : 'libre';
                    $avail = isset($j->available_at) ? date('Y-m-d H:i:s', (int) $j->available_at) : '?';
                    echo "  job#{$j->id} attempts={$j->attempts} reserved={$reserved} available={$avail}\n";
                }
            }
        } else {
            echo "[FALTA] tabla jobs\n";
        }

        if (Schema::hasTable('failed_jobs')) {
            $failed = (int) DB::table('failed_jobs')
                ->where('payload', 'like', '%SendOrderWhatsappJob%')
                ->count();
            if ($failed > 0) {
                echo "failed_jobs SendOrderWhatsappJob: {$failed}\n";
                $lastFailed = DB::table('failed_jobs')
                    ->where('payload', 'like', '%SendOrderWhatsappJob%')
                    ->orderByDesc('id')
                    ->first();
                if ($lastFailed) {
                    echo '  failed_at: '.($lastFailed->failed_at ?? '')."\n";
                    echo '  exception: '.substr((string) ($lastFailed->exception ?? ''), 0, 500)."\n";
                }
            }
        }

        echo "\nUltimas 8 notificaciones WhatsApp (cualquier numero):\n";
        $ultimas = DB::table('order_whatsapp_notifications')
            ->orderByDesc('id')
            ->limit(8)
            ->get();
        if ($ultimas->isEmpty()) {
            echo "(sin registros)\n";
        } else {
            foreach ($ultimas as $n) {
                $st = (string) ($n->status ?? '');
                echo sprintf(
                    "  #%d orden=%d %s tel=%s\n      status=%s\n      -> %s\n",
                    (int) $n->id,
                    (int) $n->id_orden_c,
                    (string) ($n->folio ?? ''),
                    (string) ($n->telefono ?? ''),
                    $st,
                    $interpretWaStatus($st)
                );
                if (trim((string) ($n->message ?? '')) !== '') {
                    echo '      msg: '.substr((string) $n->message, 0, 120)."\n";
                }
                if (trim((string) ($n->provider_message_id ?? '')) !== '') {
                    echo '      wamid: '.($n->provider_message_id ?? '')."\n";
                }
            }
        }
    }
} catch (Throwable $e) {
    echo "\n[AVISO] Cola: ".$e->getMessage()."\n";
}

if ($waStatusId > 0) {
    try {
        if (Schema::hasTable('order_whatsapp_notifications')) {
            $sectionNum = $waPhoneSearch !== '' ? '9' : '8';
            echo "\n=== {$sectionNum}. Notificacion #{$waStatusId} (detalle) ===\n";
            $one = DB::table('order_whatsapp_notifications')->where('id', $waStatusId)->first();
            if ($one) {
                $st = (string) ($one->status ?? '');
                echo '  folio: '.($one->folio ?? '')."\n";
                echo '  orden: '.($one->id_orden_c ?? '')."\n";
                echo '  telefono: '.($one->telefono ?? '')."\n";
                echo '  status: '.$st."\n";
                echo '  -> '.$interpretWaStatus($st)."\n";
                echo '  message: '.substr((string) ($one->message ?? ''), 0, 250)."\n";
                echo '  wamid: '.($one->provider_message_id ?? '(vacio)')."\n";
                echo '  creado: '.($one->created_at ?? '')."\n";
                echo '  failed_at: '.($one->failed_at ?? '')."\n";

                if ($st === 'queued' && Schema::hasTable('jobs')) {
                    $still = DB::table('jobs')->where('payload', 'like', '%SendOrderWhatsappJob%')->count();
                    echo "\n  Jobs WhatsApp pendientes ahora: {$still}\n";
                    if ($still > 0) {
                        echo "  [EN COLA] Espera al cron o abre: docheck.php?key=".DOCHECK_KEY."&wa_run_queue=1\n";
                    } else {
                        echo "  [ATENCION] status=queued pero no hay job — cron pudo fallar o cola=sync.\n";
                    }
                }
            } else {
                echo "(no existe)\n";
            }
        }
    } catch (Throwable $e) {
        echo "\n[AVISO] Notificacion: ".$e->getMessage()."\n";
    }
}

if ($waInbound) {
    echo "\n=== Chat Soporte WhatsApp ===\n";
    echo "Modulo eliminado. Las tablas wa_conversations / wa_messages ya no se usan.\n";
}

if (($_GET['wa_run_queue'] ?? '') === '1') {
    @set_time_limit(320);
    echo "\n=== Procesar 1 job ahora ===\n";
    try {
        Illuminate\Support\Facades\Artisan::call('queue:work', [
            'connection' => 'database',
            '--once' => true,
            '--timeout' => 300,
            '--tries' => 1,
        ]);
        echo trim(Illuminate\Support\Facades\Artisan::output())."\n";
        echo "Vuelve a abrir wa_cola=1 para ver si salio de cola.\n";
    } catch (Throwable $e) {
        echo '[FALLO] '.$e->getMessage()."\n";
    }
}

summary:
echo "\n=== Resumen ===\n";
if (! $f['ok']) {
    echo "RED: NO hay TLS 443 a graph.facebook.com -> ticket hosting (bloqueo).\n";
} elseif ($waReady && is_array($r2) && $r2['error'] !== '') {
    echo "RED: TLS OK pero cURL fallo -> {$r2['error']}\n";
} elseif ($waReady && is_array($r2) && $r2['http_code'] === 200) {
    if (is_array($phoneMeta) && ($phoneMeta['is_test_line'] ?? false)) {
        echo "RED y API OK. Linea de PRUEBA Meta (+1 555-650-xxxx).\n";
        echo "Agrega destinatarios en Meta (error 131030 si no estan verificados).\n";
    } else {
        echo "RED y API OK. Guarda una orden (Recepcion/Terminado/Entregado) y revisa wa_cola.\n";
    }
    if (is_array($phoneMeta)) {
        echo 'Linea emisora actual: '.$phoneMeta['display_phone_number'].' (ID '.$phoneMeta['id'].")\n";
    }
} elseif ($waReady && is_array($r2) && in_array($r2['http_code'], [401, 403], true)) {
    echo "RED OK. Corrige ACCESS_TOKEN o permisos de la app en Meta.\n";
} elseif ($waReady && is_array($r2) && $r2['http_code'] === 400) {
    echo "RED OK (Meta responde en ~{$r2['total_ms']} ms). HTTP 400 = ID o credencial incorrecta (seccion 6).\n";
    echo "Prueba: docheck.php?key=".DOCHECK_KEY."&wa_compare=TU_ID,OTRO_ID\n";
} elseif ($f['ok']) {
    echo "RED OK hacia Meta. Revisa seccion 6 (token/API) y cola.\n";
} else {
    echo "Revisa secciones anteriores.\n";
}

echo "\nErrores Meta frecuentes:\n";
echo "  131030 = destinatario no en lista de prueba (Meta → WhatsApp → API Setup)\n";
echo "  190 = token invalido/expirado\n";
echo "  ID sin display_phone_number = App ID o WABA, no Phone Number ID\n";

$appUrl = rtrim((string) config('app.url', ''), '/');
echo "\nURLs utiles:\n";
echo "  General:     docheck.php?key=".DOCHECK_KEY."\n";
echo "  Cola:        docheck.php?key=".DOCHECK_KEY."&wa_cola=1\n";
echo "  Por telefono: docheck.php?key=".DOCHECK_KEY."&wa_phone=6122885758\n";
echo "  Comparar IDs: docheck.php?key=".DOCHECK_KEY."&wa_compare=1138876072642282,OTRO_ID\n";
echo "  Descubrir:   docheck.php?key=".DOCHECK_KEY."&wa_discover=1\n";
echo "  Una notif:   &wa_status=ID\n";
echo "  Procesar job: &wa_run_queue=1\n";
echo "  Red Meta:    &wa_test_meta=1\n";
echo "  Chat entrantes: docheck.php?key=".DOCHECK_KEY."&wa_inbound=1\n";
echo "  Chat + telefono: docheck.php?key=".DOCHECK_KEY."&wa_inbound=1&wa_phone=6121684390\n";
if ($appUrl !== '') {
    echo "  Prueba envio: {$appUrl}/wa_test_send.php?key=".DOCHECK_KEY."&mode=template&to=526122885758&name=orden_recepcion&p1=OS-TEST&p2=Recepcion\n";
}
echo "\n=== Fin. BORRA public/docheck.php cuando termines ===\n";

/**
 * @return list<string>
 */
function docheckPhoneVariants(string $raw): array
{
    $digits = preg_replace('/\D+/', '', $raw) ?? '';
    if (strlen($digits) === 10) {
        $canonical = '521'.$digits;
    } elseif (preg_match('/^52(?:1)?(\d{10})$/', $digits, $m)) {
        $canonical = '521'.$m[1];
    } else {
        $canonical = $digits;
    }

    $variants = [$canonical];
    if (preg_match('/^521(\d{10})$/', $canonical, $m)) {
        $variants[] = '52'.$m[1];
    }

    return array_values(array_unique($variants));
}
