<?php

declare(strict_types=1);

/**
 * Genera docs/Diagnostico-WhatsApp-Exacto-Soporte.docx
 * Ejecutar: php scripts/generar-doc-diagnostico-whatsapp.php
 */

$outDir = 'C:\\Exacto_Documentacion';
$outFile = $outDir.'\\Diagnostico-WhatsApp-Exacto-Soporte.docx';

if (! is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}

$sections = [
    ['title' => 'Diagnóstico WhatsApp Cloud API — Exacto LP', 'level' => 0],
    ['title' => 'Sitio: soporte.exactolp.mx', 'level' => 0, 'text' => 'Documento generado a partir del análisis técnico realizado en mayo 2026. Resume preguntas, pruebas, cambios aplicados y causa raíz del fallo.'],
    ['title' => '1. Resumen ejecutivo', 'level' => 1, 'text' => "El sistema Exacto Laravel está configurado correctamente para enviar WhatsApp automático al guardar o cambiar órdenes en estatus Recepción, Terminado y Entregado.\n\nEl mensaje NO llega al cliente porque el hosting (cuenta joses16, servidor compartido cPanel/LiteSpeed) no logra establecer conexión HTTPS saliente hacia graph.facebook.com (API de Meta/WhatsApp).\n\nLa prueba definitiva es docheck.php?key=exacto99&wa_test_meta=1, que falla con:\ncURL error 28: Connection timed out after 10003 milliseconds\n\nEsto NO es un error de token, plantilla, PDF ni código Laravel: es un bloqueo o fallo de red del proveedor de hosting."],
    ['title' => '2. Objetivo del proyecto WhatsApp', 'level' => 1, 'text' => "Enviar automáticamente un mensaje de WhatsApp al cliente cuando:\n• Se registra una orden en estatus Recepción\n• La orden pasa a Terminado\n• La orden pasa a Entregado\n\nEl sistema debe tomar el teléfono de la orden, normalizarlo (ej. 6121684390 → 526121684390), usar plantillas aprobadas en Meta (orden_recepcion, orden_terminado, orden_entregado) e incluir el PDF de la orden de servicio."],
    ['title' => '3. Cómo funciona el envío automático (implementado)', 'level' => 1, 'text' => "Flujo:\n1. Usuario guarda orden en /api/ordenes/registrar (RegistrarOrdenService)\n2. Si el estatus es Recepción, Terminado o Entregado → dispatchStatusNotifications()\n3. OrderWhatsappService crea fila en order_whatsapp_notifications (status=queued)\n4. SendOrderWhatsappJob se encola en tabla jobs (QUEUE_CONNECTION=database)\n5. Cron cPanel cada minuto ejecuta: php artisan queue:work database --stop-when-empty\n6. El job llama a Meta Graph API POST .../messages con plantilla + parámetros (folio, estatus) + PDF\n\nEquivalente al curl manual:\nPOST https://graph.facebook.com/v20.0/{PHONE_NUMBER_ID}/messages\nAuthorization: Bearer {TOKEN_PERMANENTE}\nBody: type=template, name=orden_recepcion, body con OS-2026-XXX y Recepción, header documento PDF."],
    ['title' => '4. Problemas encontrados durante el desarrollo', 'level' => 1],
    ['title' => '4.1 Timeout al guardar orden (500 Request Timeout)', 'level' => 2, 'text' => "Síntoma: Al guardar orden, el navegador mostraba HTML \"Request Timeout\" en lugar de JSON.\n\nCausas identificadas:\n• WhatsApp/correo se ejecutaban en la misma petición (sync o afterResponse)\n• probeCorreoRegistrado hacía sondeo SMTP síncrono\n\nSoluciones aplicadas:\n• Cola database forzada: SendOrderWhatsappJob::dispatch()->onConnection('database')\n• WhatsApp con sendNow=false (no bloquea el guardado)\n• Validación correo solo por formato local (sin SMTP al guardar)\n\nResultado: Las órdenes OS-2026-020, 021, 022 se guardan correctamente."],
    ['title' => '4.2 Error regex en formulario (campo población)', 'level' => 2, 'text' => "Síntoma en consola: Pattern [A-Za-z\\s.'-]{2,80} is not a valid regular expression (flag /v del navegador).\n\nSolución: pattern=\"[-A-Za-z\\s.']{2,80}\" en orden_form.blade.php y try/catch en checkValidity() en orden_servicio.js."],
    ['title' => '4.3 Timeout al subir PDF a Meta (/media)', 'level' => 2, 'text' => "Síntoma en notificaciones #4, #5, #6:\ncURL error 28: Connection timed out ... graph.facebook.com/.../media\n\nCausa: El hosting no completaba la subida del PDF (~48 KB) al endpoint /media de Meta en 60 segundos.\n\nSolución aplicada: WHATSAPP_DOCUMENT_DELIVERY=link\n• Meta descarga el PDF desde URL firmada: https://soporte.exactolp.mx/wa/pdf/orden/{id}?signature=...\n• Evita llamar a /media desde el servidor\n• Ruta pública firmada en routes/web.php + OrderPdfController::showSigned()"],
    ['title' => '4.4 Timeout al enviar mensaje (/messages) — PROBLEMA ACTUAL', 'level' => 2, 'text' => "Síntoma en notificaciones #7, #8 y wa_test_meta=1:\ncURL error 28: Connection timed out ... graph.facebook.com/.../messages\n\nIncluso un GET simple al Phone Number ID (wa_test_meta=1) hace timeout en ~10 segundos.\n\nConclusión: El servidor NO puede comunicarse con Meta en absoluto (salida HTTPS bloqueada o muy degradada). Ningún cambio de código lo resolverá hasta que el hosting habilite la conexión."],
    ['title' => '5. Explicación: por qué el hosting no se comunica con Meta', 'level' => 1, 'text' => "Analogía: Es como marcar un teléfono y que nadie conteste antes de colgar. La aplicación nunca llega a \"hablar\" con Meta.\n\nFlujo normal:\nTu servidor → Internet → graph.facebook.com → HTTP 200 + JSON\n\nTu caso:\nTu servidor → (espera) → TIMEOUT error 28\n\nSi el token fuera inválido, Meta respondería en 1-2 segundos con HTTP 401 y JSON {\"error\":{\"code\":190,...}}. Ustedes nunca reciben esa respuesta.\n\nPosibles causas en hosting compartido:\n1. Firewall que bloquea salida HTTPS a ciertos dominios (Facebook/Meta)\n2. Política anti-spam que impide APIs de mensajería\n3. DNS lento o fallido para graph.facebook.com\n4. Restricciones del plan (curl externo limitado)\n5. Problema de ruta de red del datacenter hacia Meta\n\nNo es problema de: token permanente, plantillas Meta, número de teléfono del cliente, ni tamaño del PDF (con delivery=link)."],
    ['title' => '6. Reglas de Meta (WhatsApp Cloud API)', 'level' => 1, 'text' => "• Mensajes proactivos (avisos de orden sin que el cliente escriba primero): OBLIGATORIO usar plantillas aprobadas. No se puede usar solo texto libre.\n• Texto libre (type: text): Solo dentro de ventana 24 horas después de que el cliente escribió al número business.\n• Token permanente: Correcto para producción; el mismo token sirve en curl, Laravel o script PHP.\n• Un script externo NO evita las reglas de Meta ni el bloqueo de red del hosting."],
    ['title' => '7. Configuración .env requerida (producción)', 'level' => 1, 'text' => "APP_URL=https://soporte.exactolp.mx\nWHATSAPP_CLOUD_ENABLED=true\nWHATSAPP_PHONE_NUMBER_ID=1537852524373963\nWHATSAPP_ACCESS_TOKEN=(token permanente)\nWHATSAPP_VERIFY_TOKEN=(coincide con Meta webhook)\nWHATSAPP_TEMPLATE_RECEPCION=orden_recepcion\nWHATSAPP_TEMPLATE_TERMINADO=orden_terminado\nWHATSAPP_TEMPLATE_ENTREGADO=orden_entregado\nWHATSAPP_TEMPLATE_INCLUDE_DOCUMENT=true\nWHATSAPP_DOCUMENT_DELIVERY=link\nQUEUE_CONNECTION=database\n\nTras cambios en .env: borrar bootstrap/cache/config.php"],
    ['title' => '8. Historial de notificaciones (docheck 30-may-2026)', 'level' => 1, 'text' => "#1-#3: failed — error genérico contactar Meta\n#4-#6: failed — timeout /media (antes de delivery=link)\n#6 adicional: error failed_jobs UUID duplicado (secundario)\n#7 OS-2026-021: failed — timeout /messages\n#8 OS-2026-022: failed — timeout /messages\n\nNinguna notificación con wamid (Meta nunca aceptó el mensaje).\nTeléfono de prueba: 526121684390"],
    ['title' => '9. Herramientas de diagnóstico (sin SSH)', 'level' => 1, 'text' => "docheck.php?key=exacto99 — diagnóstico general\n&wa_test_meta=1 — prueba conexión Meta (GET Phone ID)\n&wa_status=8 — detalle notificación #8\n&wa_requeue_run=8 — reencolar y procesar 1 job (~3 min)\n&wa_run_queue=1 — procesar cola manualmente\n\nwa_test_send.php?key=exacto99&mode=ping — ping Meta\n&mode=template&to=526121684390 — prueba plantilla sin PDF\n\nIMPORTANTE: Borrar docheck.php y wa_test_send.php cuando terminen pruebas (exponen datos sensibles)."],
    ['title' => '10. Texto para ticket al hosting', 'level' => 1, 'text' => "Asunto: No hay salida HTTPS a graph.facebook.com (WhatsApp API)\n\nDesde mi sitio soporte.exactolp.mx (cuenta joses16) las peticiones PHP/cURL a https://graph.facebook.com fallan con:\ncURL error 28: Connection timed out\n\nNecesito conexión HTTPS saliente (puerto 443) hacia graph.facebook.com para WhatsApp Business Cloud API.\n\n¿Pueden revisar firewall, restricción de salida o bloqueo a dominios de Meta/Facebook en mi plan?\n\nPrueba realizada: GET https://graph.facebook.com/v20.0/{PHONE_NUMBER_ID} con token válido → timeout en 10 segundos."],
    ['title' => '11. Qué hacer cuando el hosting resuelva el bloqueo', 'level' => 1, 'text' => "1. Abrir docheck.php?key=exacto99&wa_test_meta=1 → debe mostrar HTTP 200 y display_phone_number\n2. Reintentar: docheck.php?key=exacto99&wa_requeue_run=8\n3. Verificar wa_status=8 → status=accepted y wamid presente\n4. Revisar WhatsApp del cliente 6121684390\n5. Guardar orden nueva de prueba (Recepción) y confirmar entrega automática"],
    ['title' => '12. Alternativas si el hosting no permite Meta', 'level' => 1, 'text' => "• Migrar a VPS/hosting que permita APIs externas (DigitalOcean, Linode, etc.)\n• Servidor intermedio (proxy) que envíe a Meta — más complejo\n• Continuar solo con notificaciones por correo hasta migrar\n• Cambiar proveedor de hosting del dominio soporte.exactolp.mx"],
    ['title' => '13. Archivos modificados en el proyecto', 'level' => 1, 'text' => "app/Services/OrderWhatsappService.php — envío, cola, PDF por link, fallback\napp/Services/RegistrarOrdenService.php — dispatchStatusNotifications\napp/Services/OrderEmailService.php — onConnection database\napp/Http/Controllers/OrderPdfController.php — showSigned() PDF firmado\napp/Jobs/SendOrderWhatsappJob.php — timeout 300\nconfig/services.php — document_delivery, fallback_without_document\nroutes/web.php — /wa/pdf/orden/{id}\npublic/docheck.php — diagnóstico sin SSH\npublic/wa_test_send.php — pruebas manuales\nresources/views/orders/orden_form.blade.php — regex población\npublic/legacy/assets/js/orden_servicio.js — validación, WhatsApp poll"],
    ['title' => '14. Conclusión final', 'level' => 1, 'text' => "El software Exacto cumple con el diseño: envío automático por estatus, teléfono de la orden, plantillas Meta, PDF adjunto vía enlace firmado, cola en background sin bloquear guardado.\n\nEl único impedimento restante es de INFRAESTRUCTURA: el hosting joses16/soporte.exactolp.mx no establece conexión saliente con graph.facebook.com.\n\nAcción requerida: Ticket al proveedor de hosting. Sin resolución de red, WhatsApp no funcionará desde este servidor sin importar scripts, tokens o plantillas."],
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
    if ($level === 0 && isset($sec['text']) && ! isset($sec['title'])) {
        continue;
    }
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
