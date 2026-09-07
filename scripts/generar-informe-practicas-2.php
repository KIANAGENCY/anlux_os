<?php

declare(strict_types=1);

/**
 * Genera docs/informe_practicas_1.docx (segundo informe de prácticas, mínimo).
 * Ejecutar: php scripts/generar-informe-practicas-2.php
 */

$root = dirname(__DIR__);
$outFile = $root.DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'informe_practicas_1.docx';

if (! is_dir(dirname($outFile))) {
    mkdir(dirname($outFile), 0755, true);
}

$sections = [
    [
        'title' => 'Informe de prácticas profesionales — Segundo avance',
        'level' => 0,
        'text' => "Proyecto: Sistema de soporte Anlux (Laravel)\nSitio: https://soporte.anlux.mx\nEmpresa: Anlux\nPeriodo: junio 2026\nDocumento: informe_practicas_1.docx",
    ],
    [
        'title' => '1. Contexto (primer informe)',
        'level' => 1,
        'text' => "En el informe anterior se documentó la migración del sistema legacy PHP a Laravel 13 para la gestión de órdenes de servicio de Anlux. Se estableció la base del proyecto: autenticación de técnicos, estructura de base de datos (orden_servicio_c, orden_servicio_t, equipos, trabajos, materiales) y el formulario de captura de órdenes.",
    ],
    [
        'title' => '2. Órdenes registradas',
        'level' => 1,
        'text' => "Módulo para consultar y administrar las órdenes activas del taller.\n\nImplementación:\n• Vista /ordenes con tabla dinámica (folio, cliente, fechas, técnico, involucrados, estatus).\n• API GET /api/ordenes alimentada por OrdenListService (búsqueda por folio o cliente, filtros por estatus y rango de fechas).\n• Cambio de estatus vía POST /api/ordenes/estatus con OrdenStatusService y registro en orden_servicio_tecnico_log.\n• Formulario /orden_servicio para alta y edición; guardado con POST /api/ordenes/registrar (RegistrarOrdenService).\n• Columnas Involucrados y Técnico con nombres descifrados desde AnluxVaultService.\n• Bloqueo de edición concurrente (OrderEditLockController) para evitar conflictos entre técnicos.\n• Acceso desde el menú principal y la celda «Órdenes registradas» del flujo de navegación.",
    ],
    [
        'title' => '3. Historial de órdenes',
        'level' => 1,
        'text' => "Módulo para consultar órdenes archivadas y abrir su comprobante PDF.\n\nImplementación:\n• Vista /historial (HistorialOrdenesController + historial_page.blade.php).\n• Misma API /api/ordenes reutilizada para cargar el listado vía AJAX (historial_laravel.js).\n• Búsqueda por folio o nombre de cliente y filtros por estatus (Recepción, En proceso, Terminado, Entregado) y fechas.\n• Tabla con folio, cliente, fecha de entrada, estatus y acción «Abrir pestaña» para ver el PDF en /pdf/orden/{id}.\n• Acceso desde el menú de inicio y la celda «Historial PDF» del flujo de navegación.",
    ],
    [
        'title' => '4. Conclusión',
        'level' => 1,
        'text' => "Se completó la implementación de los módulos de órdenes registradas e historial de órdenes en Laravel. Los técnicos pueden listar, filtrar, cambiar estatus y capturar órdenes desde el formulario; el historial permite localizar órdenes anteriores y consultar su PDF sin salir del sistema.",
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
    fwrite(STDERR, "Se requiere extensión php-zip (ZipArchive).\n");
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
