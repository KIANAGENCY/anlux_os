<?php

declare(strict_types=1);

/**
 * Genera documento Word para soporte Akky / hosting.
 * Ejecutar: php scripts/generar-doc-diagnostico-akky.php
 */

$root = dirname(__DIR__);
$outFile = $root.DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'Diagnostico-WhatsApp-Akky-Exacto.docx';

if (! is_dir(dirname($outFile))) {
    mkdir(dirname($outFile), 0755, true);
}

$sections = [
    [
        'title' => 'Diagnóstico técnico — WhatsApp Cloud API',
        'level' => 0,
        'text' => "Solicitud de soporte a Akky / proveedor de hosting\n\nCliente: Exacto LP\nSitio: https://soporte.exactolp.mx\nAplicación: Exacto Laravel (órdenes de servicio + notificaciones WhatsApp)\nFecha del diagnóstico: 2 de junio de 2026\nDocumento preparado para ingeniería de red / soporte avanzado del hosting",
    ],
    [
        'title' => '1. Resumen ejecutivo',
        'level' => 1,
        'text' => "La aplicación en soporte.exactolp.mx está correctamente configurada para WhatsApp Business Cloud API (Meta). Token, Phone Number ID, plantillas y cola Laravel funcionan a nivel de software.\n\nEl envío de mensajes NO es posible porque el servidor de hosting NO establece conexión saliente HTTPS (puerto 443) hacia graph.facebook.com.\n\nPrueba automatizada (docheck.php) del 2-jun-2026:\n• Conexión TLS a www.google.com:443 → EXITOSA (~53 ms)\n• Conexión TLS a graph.facebook.com:443 → FALLIDA (errno 110, timeout ~15 s)\n• cURL GET a Graph API con token válido → cURL error 28 (timeout)\n\nConclusión: bloqueo o filtrado selectivo hacia infraestructura Meta/Facebook, no fallo de aplicación ni de credenciales Meta.",
    ],
    [
        'title' => '2. Datos del entorno afectado',
        'level' => 1,
        'text' => "Servidor (hostname): altar22.supremepanel22.com\nPanel: Supreme Panel / hosting compartido (cuenta referida: joses16)\nPHP: 8.4.21\nSAPI: LiteSpeed\nDominio: soporte.exactolp.mx\nAPP_URL: https://soporte.exactolp.mx\n\nExtensiones PHP verificadas (OK):\n• curl / curl_init\n• openssl\n• fsockopen permitido (no en disable_functions)\n• allow_url_fopen: On",
    ],
    [
        'title' => '3. Resultados de pruebas de red (2-jun-2026)',
        'level' => 1,
        'text' => "Herramienta: public/docheck.php?key=exacto99 (diagnóstico sin SSH)\n\n--- DNS ---\n[OK] graph.facebook.com resuelve a: 57.144.204.141 (registro A)\n→ El problema NO es resolución DNS.\n\n--- Prueba control (salida 443 genérica) ---\nDestino: www.google.com:443\nResultado: [OK] TLS completado en ~53,3 ms\n→ El servidor SÍ tiene salida HTTPS general a Internet.\n\n--- Prueba objetivo (WhatsApp / Meta) ---\nDestino: graph.facebook.com:443\nResultado: [FALLO] errno=110 Connection timed out (~15.015 ms)\n→ No se completa handshake TLS hacia Meta.\n\n--- HTTP cURL sin token ---\nURL: https://graph.facebook.com/\nResultado: cURL #28 — conexión agotada (~15.003 ms), connect_ms=0, ip vacío\n\n--- Graph API con token (Phone Number ID) ---\nURL: https://graph.facebook.com/v20.0/1537852524373963?fields=display_phone_number,verified_name\nWHATSAPP_CLOUD_ENABLED: true\nACCESS_TOKEN: presente (195 caracteres, prefijo EAAV…)\nResultado cURL directo: #28 timeout ~15 s\nResultado Laravel Http: #28 timeout ~60 s\n\n→ Meta nunca devuelve HTTP 401/403; no hay respuesta HTTP. Indica bloqueo de red, no token inválido.",
    ],
    [
        'title' => '4. Interpretación para ingeniería de red',
        'level' => 1,
        'text' => "4.1 Qué significa errno 110 y cURL 28\nerrno 110 (ETIMEDOUT): el intento de conexión TCP/TLS hacia graph.facebook.com no obtuvo respuesta dentro del tiempo límite.\ncURL error 28: Operation timeout — coherente con firewall, ACL de salida, routing o filtro por destino.\n\n4.2 Por qué NO es un problema de token o de Meta Business\nSi el token fuera inválido o el Phone Number ID incorrecto, la API de Meta respondería en 1–3 segundos con HTTP 401 o 403 y JSON {\"error\":{\"code\":190,...}}.\nEn este caso no hay código HTTP ni cuerpo JSON: la conexión no se establece.\n\n4.3 Bloqueo selectivo (hipótesis confirmada por prueba control)\nGoogle:443 OK + graph.facebook.com:443 FAIL → patrón típico de:\n• Firewall de salida con lista de bloqueo para dominios/AS de Meta/Facebook\n• Política anti-abuse en hosting compartido contra APIs de mensajería\n• Filtrado L7 o DNS filtering hacia *.facebook.com / graph.facebook.com\n• Restricción del plan de hosting sobre destinos de redes sociales\n\n4.4 IP observada en DNS\ngraph.facebook.com → 57.144.204.141\nSolicitamos permitir salida TCP 443 a este host y, si aplica, rangos oficiales de Meta (pueden rotar; lo ideal es permitir por nombre graph.facebook.com).",
    ],
    [
        'title' => '5. Qué necesitamos que revise Akky / hosting',
        'level' => 1,
        'text' => "1. Confirmar si existe firewall o mod_security que bloquee salida HTTPS a graph.facebook.com o IPs de Meta.\n2. Habilitar tráfico saliente TCP puerto 443 (TLS 1.2+) hacia:\n   • graph.facebook.com\n   • Subdominios necesarios para WhatsApp Cloud API (graph.facebook.com es el endpoint principal).\n3. Verificar que no haya política del plan «Premium» / compartido que impida APIs de Facebook/WhatsApp.\n4. Si usan filtro por IP, incluir rangos AS de Meta o resolver dinámicamente el FQDN graph.facebook.com.\n5. Tras el cambio, confirmar con:\n   curl -v --connect-timeout 15 \"https://graph.facebook.com/v20.0/1537852524373963?fields=display_phone_number\" -H \"Authorization: Bearer {TOKEN}\"\n   desde el servidor altar22 (debe devolver HTTP 200 en pocos segundos).",
    ],
    [
        'title' => '6. Qué NO es el problema (ya descartado)',
        'level' => 1,
        'text' => "• Código Laravel / Exacto: cola, jobs y OrderWhatsappService implementados.\n• Variables .env: WHATSAPP_CLOUD_ENABLED, PHONE_NUMBER_ID y ACCESS_TOKEN configurados.\n• Plantillas Meta: orden_recepcion, orden_terminado, orden_entregado (aprobadas en Business Manager).\n• PDF adjunto: modo WHATSAPP_DOCUMENT_DELIVERY=link (Meta descarga PDF desde URL firmada en soporte.exactolp.mx; no usa /media desde servidor).\n• DNS local: graph.facebook.com resuelve correctamente.\n• PHP sin curl/openssl: extensiones OK.\n• Salida 443 totalmente caída: Google responde OK.",
    ],
    [
        'title' => '7. Contexto del negocio (por qué es crítico)',
        'level' => 1,
        'text' => "Exacto envía WhatsApp automático al cliente cuando una orden de servicio cambia a:\n• Recepción\n• Terminado\n• Entregado\n\nEl teléfono se toma de la orden; se usan plantillas aprobadas por Meta con folio y estatus; se adjunta PDF de la orden.\n\nFlujo técnico resumido:\n1. Guardado de orden → SendOrderWhatsappJob en cola (database)\n2. Cron: php artisan queue:work database --stop-when-empty\n3. Job → POST https://graph.facebook.com/v20.0/{PHONE_NUMBER_ID}/messages\n\nSin salida 443 a graph.facebook.com, ningún cliente recibe notificación por WhatsApp.",
    ],
    [
        'title' => '8. Cómo reproducir el fallo (para soporte)',
        'level' => 1,
        'text' => "Opción A — Navegador (sin SSH):\nhttps://soporte.exactolp.mx/docheck.php?key=exacto99\n\nOpción B — Con prueba Laravel Http:\nhttps://soporte.exactolp.mx/docheck.php?key=exacto99&wa_test_meta=1\n\nOpción C — Script alternativo:\nhttps://soporte.exactolp.mx/wa_test_send.php?key=exacto99&mode=ping\n\nResultado esperado HOY: sección 3 [FALLO] hacia graph.facebook.com y cURL #28 en sección 4 y 6.\n\nResultado esperado TRAS corrección:\n• Sección 3 [OK] TLS a graph.facebook.com\n• Sección 6a HTTP 200 con display_phone_number y verified_name",
    ],
    [
        'title' => '9. Texto sugerido para ticket (copiar/pegar)',
        'level' => 1,
        'text' => "Asunto: Habilitar salida HTTPS (puerto 443) a graph.facebook.com — WhatsApp Cloud API\n\nEstimado equipo Akky / soporte hosting:\n\nDesde nuestro sitio https://soporte.exactolp.mx (servidor altar22.supremepanel22.com, PHP 8.4 LiteSpeed), las conexiones salientes hacia graph.facebook.com fallan con timeout.\n\nEvidencia:\n• TLS a www.google.com:443 — OK (~53 ms)\n• TLS a graph.facebook.com:443 — TIMEOUT (errno 110, ~15 s)\n• GET Graph API con token válido — cURL error 28\n• DNS: graph.facebook.com → 57.144.204.141 (resuelve bien)\n\nSolicitamos habilitar tráfico saliente TCP 443 (HTTPS) hacia graph.facebook.com para WhatsApp Business Cloud API (Meta). Actualmente parece bloqueo selectivo a infraestructura Facebook/Meta.\n\n¿Pueden confirmar si hay firewall, restricción del plan o filtro que bloquee estos destinos y aplicar la excepción necesaria?\n\nGracias.",
    ],
    [
        'title' => '10. Criterios de aceptación (cierre del ticket)',
        'level' => 1,
        'text' => "Consideraremos resuelto el incidente cuando, ejecutado desde el mismo servidor:\n\n1. docheck.php?key=exacto99 muestre [OK] en TLS 443 a graph.facebook.com.\n2. docheck.php?key=exacto99&wa_test_meta=1 muestre HTTP 200 y datos display_phone_number / verified_name.\n3. Una notificación WhatsApp de prueba quede en status=accepted con provider_message_id (wamid) en base de datos.\n\nHasta entonces, el software Exacto permanece operativo para órdenes y correo; solo WhatsApp vía Meta permanece bloqueado por red.",
    ],
    [
        'title' => '11. Contacto y anexos',
        'level' => 1,
        'text' => "Anexo técnico en servidor: public/docheck.php (herramienta de diagnóstico; debe eliminarse tras resolver el caso por seguridad).\n\nConfiguración WhatsApp verificada en aplicación:\n• API version: v20.0\n• Base URL: https://graph.facebook.com\n• Phone Number ID: 1537852524373963\n• Document delivery: link (PDF por URL firmada en el mismo dominio)\n\nFin del documento.",
    ],
];

function xmlEscape(string $s): string
{
    return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function paragraphXml(string $text, bool $bold = false): string
{
    $parts = explode("\n", $text);
    $xml = '';
    foreach ($parts as $i => $line) {
        if ($i > 0) {
            $xml .= '<w:br/>';
        }
        $run = $bold
            ? '<w:r><w:rPr><w:b/></w:rPr><w:t xml:space="preserve">'.xmlEscape($line).'</w:t></w:r>'
            : '<w:r><w:t xml:space="preserve">'.xmlEscape($line).'</w:t></w:r>';
        $xml .= $run;
    }

    return '<w:p>'.$xml.'</w:p>';
}

function headingXml(string $text, int $level): string
{
    $size = match ($level) {
        0 => '36',
        1 => '28',
        2 => '24',
        default => '22',
    };

    return '<w:p><w:pPr><w:pStyle w:val="Heading'.$level.'"/></w:pPr>'
        .'<w:r><w:rPr><w:b/><w:sz w:val="'.$size.'"/></w:rPr>'
        .'<w:t xml:space="preserve">'.xmlEscape($text).'</w:t></w:r></w:p>';
}

$body = '';
foreach ($sections as $sec) {
    $level = $sec['level'] ?? 1;
    $body .= headingXml($sec['title'], min(2, max(0, $level)));
    if (isset($sec['text'])) {
        $body .= paragraphXml($sec['text']);
    }
    $body .= paragraphXml('');
}

$documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
    .'<w:body>'.$body.'<w:sectPr/></w:body></w:document>';

$contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
    .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
    .'<Default Extension="xml" ContentType="application/xml"/>'
    .'<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
    .'</Types>';

$rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
    .'</Relationships>';

$docRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"></Relationships>';

if (! class_exists(ZipArchive::class)) {
    fwrite(STDERR, "Se requiere extensión zip de PHP.\n");
    exit(1);
}

$zip = new ZipArchive();
if ($zip->open($outFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "No se pudo crear {$outFile}\n");
    exit(1);
}
$zip->addFromString('[Content_Types].xml', $contentTypes);
$zip->addFromString('_rels/.rels', $rels);
$zip->addFromString('word/document.xml', $documentXml);
$zip->addFromString('word/_rels/document.xml.rels', $docRels);
$zip->close();

echo "Generado: {$outFile}\n";
