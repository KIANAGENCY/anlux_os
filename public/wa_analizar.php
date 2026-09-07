<?php

declare(strict_types=1);

/**
 * Analiza por qué una plantilla WhatsApp no llegó al celular
 * aunque Meta la haya aceptado (status=accepted).
 *
 * Uso:
 *   /wa_analizar.php?key=anlux99&id=52
 *   /wa_analizar.php?key=anlux99&folio=OS-2026-005
 *   /wa_analizar.php?key=anlux99&phone=6121684390
 *
 * BORRAR del servidor cuando termines el diagnóstico.
 */

const WA_ANALIZAR_KEY = 'anlux99';

if (($_GET['key'] ?? '') !== WA_ANALIZAR_KEY) {
    http_response_code(403);
    exit('Forbidden — usa ?key='.WA_ANALIZAR_KEY);
}

header('Content-Type: text/plain; charset=utf-8');

$root = dirname(__DIR__);
if (! is_file($root.'/vendor/autoload.php')) {
    exit("Falta vendor/autoload.php\n");
}

require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

$id = (int) ($_GET['id'] ?? 0);
$folio = trim((string) ($_GET['folio'] ?? ''));
$phoneSearch = preg_replace('/\D+/', '', trim((string) ($_GET['phone'] ?? ''))) ?? '';

echo "=== ANLUX — Análisis entrega WhatsApp ===\n";
echo 'Fecha: '.date('Y-m-d H:i:s')."\n\n";

if (! Schema::hasTable('order_whatsapp_notifications')) {
    exit("[FALLO] No existe la tabla order_whatsapp_notifications.\n");
}

$query = DB::table('order_whatsapp_notifications')->orderByDesc('id');
if ($id > 0) {
    $query->where('id', $id);
} elseif ($folio !== '') {
    $query->where('folio', $folio);
} elseif ($phoneSearch !== '') {
    $query->where('telefono', 'like', '%'.$phoneSearch.'%');
} else {
    exit(
        "Indica id, folio o phone.\n".
        "Ejemplos:\n".
        "  wa_analizar.php?key=".WA_ANALIZAR_KEY."&id=52\n".
        "  wa_analizar.php?key=".WA_ANALIZAR_KEY."&folio=OS-2026-005\n".
        "  wa_analizar.php?key=".WA_ANALIZAR_KEY."&phone=6121684390\n"
    );
}

$rows = $query->limit(5)->get();
if ($rows->isEmpty()) {
    exit("[FALLO] No se encontró ninguna notificación con esos filtros.\n");
}

$token = trim((string) config('services.whatsapp.access_token', ''));
$phoneId = trim((string) config('services.whatsapp.phone_number_id', ''));
$version = trim((string) config('services.whatsapp.graph_version', 'v20.0'));
$base = rtrim((string) config('services.whatsapp.base_url', 'https://graph.facebook.com'), '/');
$lang = trim((string) config('services.whatsapp.language', 'es_MX'));
$tplRecepcion = trim((string) config('services.whatsapp.templates.recepcion', ''));
$includeDoc = filter_var(config('services.whatsapp.template_include_document', true), FILTER_VALIDATE_BOOL);
$docDelivery = strtolower((string) config('services.whatsapp.document_delivery', 'link'));
$cloudOn = filter_var(config('services.whatsapp.enabled', false), FILTER_VALIDATE_BOOL);
$anluxOn = (bool) config('anlux.whatsapp_notifications_enabled', false);

echo "--- Config servidor ---\n";
echo 'WHATSAPP_CLOUD_ENABLED: '.($cloudOn ? 'true' : 'false')."\n";
echo 'ANLUX_WHATSAPP_NOTIFICATIONS: '.($anluxOn ? 'true' : 'false')."\n";
echo 'PHONE_NUMBER_ID: '.($phoneId !== '' ? $phoneId : '(vacío)')."\n";
echo 'TOKEN: '.($token !== '' ? substr($token, 0, 6).'...('.strlen($token).' chars)' : '(vacío)')."\n";
echo 'LANGUAGE: '.$lang."\n";
echo 'TEMPLATE_RECEPCION: '.$tplRecepcion."\n";
echo 'INCLUDE_DOCUMENT: '.($includeDoc ? 'true' : 'false')."\n";
echo 'DOCUMENT_DELIVERY: '.$docDelivery."\n\n";

$http = null;
if ($token !== '') {
    $http = Http::withToken($token)
        ->acceptJson()
        ->connectTimeout(20)
        ->timeout(45);
}

if ($http !== null && $phoneId !== '') {
    echo "--- Línea emisora Meta ---\n";
    try {
        $resp = $http->get("{$base}/{$version}/{$phoneId}", [
            'fields' => 'id,display_phone_number,verified_name,quality_rating,code_verification_status,is_official_business_account',
        ]);
        $body = $resp->json();
        if (! is_array($body)) {
            $body = [];
        }
        if ($resp->successful()) {
            $display = (string) ($body['display_phone_number'] ?? '');
            $name = (string) ($body['verified_name'] ?? '');
            $quality = (string) ($body['quality_rating'] ?? '');
            echo "Número de negocio: {$display}\n";
            echo "Nombre verificado: {$name}\n";
            echo "Calidad: {$quality}\n";
            if (str_contains($display, '555') || str_starts_with(preg_replace('/\D+/', '', $display) ?? '', '1555')) {
                echo "[ATENCIÓN] Parece línea de PRUEBA Meta. Solo llegan a números agregados en la lista de prueba.\n";
            }
        } else {
            echo '[FALLO] No se pudo leer Phone Number ID. HTTP '.$resp->status()."\n";
            echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n";
        }
    } catch (Throwable $e) {
        echo '[FALLO] Graph phone: '.$e->getMessage()."\n";
    }
    echo "\n";
}

$n = 0;
foreach ($rows as $row) {
    $n++;
    $status = trim((string) ($row->status ?? ''));
    $wamid = trim((string) ($row->provider_message_id ?? ''));
    $telefono = trim((string) ($row->telefono ?? ''));
    $template = trim((string) ($row->template_name ?? ''));
    $message = trim((string) ($row->message ?? ''));

    echo str_repeat('=', 64)."\n";
    echo "Notificación #{$row->id} ({$n}/{$rows->count()})\n";
    echo str_repeat('=', 64)."\n";
    echo 'folio: '.($row->folio ?? '')."\n";
    echo 'id_orden_c: '.($row->id_orden_c ?? '')."\n";
    echo 'estatus orden: '.($row->estatus ?? '')."\n";
    echo "telefono destino: {$telefono}\n";
    echo "template: {$template}\n";
    echo "status BD: {$status}\n";
    echo "message BD: {$message}\n";
    echo 'wamid: '.($wamid !== '' ? $wamid : '(vacío)')."\n";
    echo 'queued_at: '.($row->queued_at ?? '')."\n";
    echo 'sent_at: '.($row->sent_at ?? '')."\n";
    echo 'delivered_at: '.($row->delivered_at ?? '(vacío)')."\n";
    echo 'read_at: '.($row->read_at ?? '(vacío)')."\n";
    echo 'failed_at: '.($row->failed_at ?? '(vacío)')."\n";
    echo 'created_at: '.($row->created_at ?? '')."\n";
    echo 'updated_at: '.($row->updated_at ?? '')."\n\n";

    $response = json_decode((string) ($row->response_json ?? ''), true);
    if (! is_array($response)) {
        $response = [];
    }
    $webhook = json_decode((string) ($row->webhook_json ?? ''), true);
    if (! is_array($webhook)) {
        $webhook = [];
    }
    $payload = json_decode((string) ($row->payload_json ?? ''), true);
    if (! is_array($payload)) {
        $payload = [];
    }

    echo "--- Respuesta Meta al enviar (response_json) ---\n";
    if ($response === []) {
        echo "(vacío)\n\n";
    } else {
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n\n";
        $contacts = $response['contacts'][0] ?? null;
        if (is_array($contacts)) {
            $waId = (string) ($contacts['wa_id'] ?? '');
            $input = (string) ($contacts['input'] ?? '');
            echo "Meta normalizó el destino:\n";
            echo "  input: {$input}\n";
            echo "  wa_id: {$waId}\n";
            if ($waId !== '' && $telefono !== '' && ! str_ends_with($telefono, $waId) && $waId !== $telefono) {
                echo "  [ATENCIÓN] wa_id distinto al telefono guardado. Puede ser formato 52 vs 521.\n";
            }
            echo "\n";
        }
        $err = $response['error'] ?? null;
        if (is_array($err)) {
            echo '[ERROR META] code='.($err['code'] ?? '?').' '.$err['message']."\n";
            if (! empty($err['error_user_msg'])) {
                echo '  user_msg: '.$err['error_user_msg']."\n";
            }
            echo "\n";
        }
    }

    echo "--- Webhook de entrega (webhook_json) ---\n";
    if ($webhook === []) {
        echo "(vacío) — Meta aún NO reportó delivered/read/failed a este registro.\n";
        echo "Si el webhook no está bien configurado, te quedas en accepted y no sabes si llegó.\n\n";
    } else {
        echo json_encode($webhook, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n\n";
        $whStatus = trim((string) ($webhook['status'] ?? ''));
        $whErrors = $webhook['errors'] ?? null;
        if ($whStatus !== '') {
            echo "Último status webhook: {$whStatus}\n";
        }
        if (is_array($whErrors) && $whErrors !== []) {
            echo "[ERROR WEBHOOK]\n";
            echo json_encode($whErrors, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n";
        }
        echo "\n";
    }

    // Buscar link PDF en response/payload si aplica.
    $pdfHint = '';
    $rawPayload = (string) ($row->payload_json ?? '');
    if (preg_match('#https?://[^\s"\']+/wa/pdf/orden/\d+[^\s"\']*#', (string) ($row->response_json ?? '').' '.$rawPayload, $m)) {
        $pdfHint = $m[0];
    }

    echo "--- Veredicto ---\n";
    $hallazgos = [];

    if ($status === 'accepted' && empty($row->delivered_at) && $webhook === []) {
        $hallazgos[] = 'Meta ACEPTÓ el envío (wamid existe), pero aún no hay webhook de delivered/failed.';
        $hallazgos[] = 'Esto suele significar: 1) el mensaje está en tránsito, 2) el celular no tiene WhatsApp / número incorrecto, 3) el webhook no está recibiendo statuses, o 4) Meta filtró la entrega después.';
    }
    if ($status === 'delivered') {
        $hallazgos[] = 'Meta confirma ENTREGADO al dispositivo. Si no lo ves, revisa carpeta de spam/negocios o número equivocado.';
    }
    if ($status === 'failed') {
        $hallazgos[] = 'Meta/Anlux marcaron FALLO. Lee message y webhook_json.';
    }
    if ($status === 'queued') {
        $hallazgos[] = 'Sigue en cola local: no salió a Meta todavía.';
    }
    if ($template !== '' && $tplRecepcion !== '' && ($row->estatus ?? '') === 'Recepción' && $template !== $tplRecepcion) {
        $hallazgos[] = "Plantilla enviada ({$template}) distinta a WHATSAPP_TEMPLATE_RECEPCION ({$tplRecepcion}).";
    }
    if (str_contains(mb_strtolower($message), 'pdf por enlace') || $includeDoc) {
        $hallazgos[] = 'Se envió con PDF por enlace. Si la plantilla en Meta NO tiene encabezado DOCUMENT, a veces Meta acepta raro o el cliente ve mensaje incompleto.';
    }
    if ($telefono !== '' && ! preg_match('/^521\d{10}$/', $telefono) && ! preg_match('/^52\d{10}$/', $telefono)) {
        $hallazgos[] = "Formato de teléfono raro: {$telefono}. Para México suele ser 521 + 10 dígitos.";
    }
    if ($wamid === '' && $status === 'accepted') {
        $hallazgos[] = 'status=accepted pero sin wamid: inconsistente; revisa response_json.';
    }

    if ($hallazgos === []) {
        echo "Sin anomalías obvias en BD. Revisa el celular destino y el webhook Meta.\n";
    } else {
        foreach ($hallazgos as $i => $h) {
            echo ($i + 1).") {$h}\n";
        }
    }

    echo "\n--- Qué revisar ahora ---\n";
    echo "1) Abre WhatsApp en el número ".($telefono !== '' ? $telefono : '(destino)')." y busca chats de negocio / archivados.\n";
    echo "2) En Meta Developers → WhatsApp → Configuration, el webhook debe apuntar a:\n";
    echo '   '.rtrim((string) config('app.url'), '/')."/webhooks/whatsapp/cloud\n";
    echo "   y suscribir el campo: messages\n";
    echo "3) Si delivered_at sigue vacío tras varios minutos, el webhook probablemente no está actualizando statuses.\n";
    echo "4) Prueba plantilla mínima sin PDF:\n";
    echo '   wa_test_send.php?key='.WA_ANALIZAR_KEY.'&mode=template&to='.rawurlencode($telefono !== '' ? $telefono : '521XXXXXXXXXX').'&name='.rawurlencode($template !== '' ? $template : 'orden_recepcion').'&p1='.rawurlencode((string) ($row->folio ?? 'OS-TEST'))."&p2=Recepcion\n";

    if ($pdfHint !== '') {
        echo "5) Link PDF detectado (puede expirar): {$pdfHint}\n";
    }

    // Intento: consultar wamid vía Graph (a menudo no está permitido; se reporta).
    if ($http !== null && $wamid !== '') {
        echo "\n--- Consulta Graph del wamid (si Meta lo permite) ---\n";
        try {
            $wResp = $http->get("{$base}/{$version}/{$wamid}", [
                'fields' => 'id,status,pricing,errors,recipient_id',
            ]);
            $wBody = $wResp->json();
            if (! is_array($wBody)) {
                $wBody = [];
            }
            echo 'HTTP '.$wResp->status()."\n";
            echo json_encode($wBody, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n";
            if (! $wResp->successful()) {
                echo "(Normal si Meta no permite leer el mensaje por ID; confía en webhook_json.)\n";
            }
        } catch (Throwable $e) {
            echo 'No consultable: '.$e->getMessage()."\n";
        }
    }

    echo "\n";
}

echo "=== Fin análisis ===\n";
echo "Si status=accepted y webhook_json vacío: Anlux SÍ envió; falta confirmar entrega vía webhook o en el celular.\n";
