# Genera docs/Informe_Global_Practicas_Anlux.docx
$root = Split-Path -Parent $PSScriptRoot
$outDir = Join-Path $root "docs"
$outFile = Join-Path $outDir "Informe_Global_Practicas_Anlux.docx"
$tempDir = Join-Path $env:TEMP "informe_global_docx_$(Get-Random)"

New-Item -ItemType Directory -Force -Path $outDir | Out-Null
if (Test-Path $tempDir) { Remove-Item -Recurse -Force $tempDir }
New-Item -ItemType Directory -Force -Path "$tempDir\word\_rels" | Out-Null
New-Item -ItemType Directory -Force -Path "$tempDir\_rels" | Out-Null

function Escape-Xml([string]$s) {
    return [System.Security.SecurityElement]::Escape($s)
}

function Para([string]$text, [bool]$justify = $true) {
    $lines = $text -split "`n"
    $runs = ""
    for ($i = 0; $i -lt $lines.Count; $i++) {
        if ($i -gt 0) { $runs += "<w:br/>" }
        $runs += "<w:r><w:t xml:space=`"preserve`">$(Escape-Xml $lines[$i])</w:t></w:r>"
    }
    $jc = if ($justify) { '<w:jc w:val="both"/>' } else { '' }
    return "<w:p><w:pPr>$jc<w:spacing w:after=`"120`" w:line=`"360`" w:lineRule=`"auto`"/></w:pPr>$runs</w:p>"
}

function Center([string]$text, [bool]$bold = $false, [string]$size = "24") {
    $b = if ($bold) { "<w:b/>" } else { "" }
    return "<w:p><w:pPr><w:jc w:val=`"center`"/><w:spacing w:after=`"120`" w:line=`"360`" w:lineRule=`"auto`"/></w:pPr><w:r><w:rPr>$b<w:sz w:val=`"$size`"/></w:rPr><w:t xml:space=`"preserve`">$(Escape-Xml $text)</w:t></w:r></w:p>"
}

function Head([string]$text) {
    return "<w:p><w:pPr><w:spacing w:before=`"240`" w:after=`"120`" w:line=`"360`" w:lineRule=`"auto`"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val=`"28`"/></w:rPr><w:t xml:space=`"preserve`">$(Escape-Xml $text)</w:t></w:r></w:p>"
}

function Field([string]$label, [string]$value) {
    return "<w:p><w:pPr><w:spacing w:after=`"80`" w:line=`"360`" w:lineRule=`"auto`"/></w:pPr><w:r><w:rPr><w:b/></w:rPr><w:t xml:space=`"preserve`">$(Escape-Xml $label)</w:t></w:r><w:r><w:t xml:space=`"preserve`"> $(Escape-Xml $value)</w:t></w:r></w:p>"
}

function PageBreak() { return "<w:p><w:r><w:br w:type=`"page`"/></w:r></w:p>" }

$body = @"
$(Center "DEPARTAMENTO DE SERVICIOS ESCOLARES" $true "22")
$(Center "OFICINA DE PRÁCTICAS PROFESIONALES" $true "22")
$(Center "INFORME GLOBAL" $true "36")
$(Para "")
$(Head "1.- NOMBRE DE LA EMPRESA:")
$(Field "DOMICILIO:" "Los Cabos, Baja California Sur, México.")
$(Field "SERVICIOS QUE PRESTA (LA EMPRESA):" "Reparación y mantenimiento de equipos de cómputo, venta de refacciones, desarrollo de software a la medida y soporte técnico especializado para empresas y particulares de la región.")
$(Para "")
$(Field "NOMBRE DE LA EMPRESA:" "Anlux.")
$(Field "NOMBRE DEL ALUMNO:" "[Completar con su nombre]")
$(Field "NÚMERO DE CONTROL:" "[Completar]")
$(Field "ESPECIALIDAD:" "Técnico en Programación")
$(Field "SEMESTRE:" "VI")
$(Field "GRUPO:" "[Completar]")
$(Field "GENERACIÓN:" "2023 – 2026")
$(PageBreak)
$(Head "2.- PRESENTACIÓN (INTRODUCCIÓN)")
$(Para "El presente informe global documenta las actividades desarrolladas durante las prácticas profesionales realizadas en Anlux, conocida comercialmente como Anlux, empresa dedicada al servicio técnico de equipos de cómputo y al desarrollo de soluciones informáticas para la gestión de su operación diaria.")
$(Para "La práctica se enmarca en el proyecto de modernización del sistema interno Anlux, plataforma utilizada por los técnicos del taller para registrar órdenes de servicio, dar seguimiento al estatus de reparaciones, generar comprobantes en PDF y comunicarse con los clientes. Históricamente, el sistema operaba sobre PHP tradicional con arquitectura monolítica; a partir del 30 de marzo de 2026 se inició formalmente la migración y ampliación del mismo hacia el framework Laravel, con despliegue en el subdominio soporte.anlux.mx, con el fin de mejorar la seguridad, el mantenimiento del código y la capacidad de integración con servicios externos como correo electrónico y WhatsApp Business.")
$(Para "Durante el periodo de práctica se trabajó de manera colaborativa con el asesor empresarial y el equipo técnico de la empresa, aplicando conocimientos adquiridos en el bachillerato tecnológico en materias como programación orientada a objetos, bases de datos, redes de computadoras y desarrollo web. El entorno de desarrollo local se configuró con Laragon en Windows, utilizando PHP 8.3, MySQL, Composer y Git para el control de versiones. La base de datos existente del sistema legacy se conservó para garantizar continuidad operativa y compatibilidad con los registros históricos de órdenes de servicio.")
$(Para "El proyecto Anlux Laravel representa una evolución significativa respecto al sistema anterior. Se adoptaron patrones de diseño propios del ecosistema Laravel —controladores, servicios, políticas, jobs en cola, migraciones y vistas Blade— manteniendo la interfaz familiar para los técnicos mediante la reutilización selectiva de activos JavaScript y CSS del sistema previo. Esta estrategia de migración gradual, documentada en el plan de cutover del proyecto, permitió validar cada módulo antes de su uso en producción y contar con un mecanismo de rollback en caso de incidencias.")
$(Para "A lo largo de las semanas de trabajo se abordaron módulos críticos para la operación del taller: autenticación de personal, captura y edición de órdenes, listado con filtros, historial de servicios, generación de PDF, panel de administración, notificaciones automáticas y un módulo de soporte por WhatsApp. Asimismo, se elaboraron páginas legales exigidas por Meta para la integración con WhatsApp Cloud API, y se documentó el proceso de despliegue en hosting compartido con cPanel. Este informe sintetiza el alcance, las actividades, los resultados y las conclusiones de dicho proyecto.")
$(Head "3.- OBJETIVO DE LA PRÁCTICA")
$(Para "El objetivo general de la práctica profesional consistió en participar en el desarrollo, migración y puesta en operación del sistema web Anlux sobre Laravel, contribuyendo a la digitalización y mejora de los procesos de gestión de órdenes de servicio de la empresa Anlux.")
$(Para "Los objetivos específicos fueron los siguientes:`n• Analizar la arquitectura y el flujo de datos del sistema legacy PHP para identificar equivalencias en Laravel.`n• Implementar el módulo de autenticación de técnicos, respetando la tabla login existente y el mecanismo de cifrado de datos sensibles (teléfono y domicilio del cliente).`n• Desarrollar las vistas y APIs para el listado de órdenes activas, el formulario de captura/edición y el historial de órdenes archivadas.`n• Integrar la generación de comprobantes PDF y su visualización en línea desde el navegador.`n• Configurar notificaciones automáticas por correo electrónico y WhatsApp al cambiar el estatus de una orden (Recepción, Terminado, Entregado).`n• Implementar el módulo de chat de soporte WhatsApp para que los técnicos respondan mensajes de clientes desde el sistema.`n• Desplegar la aplicación en el entorno de producción soporte.anlux.mx y documentar procedimientos operativos.")
$(Head "PERÍODO DE REALIZACIÓN")
$(Para "Las prácticas profesionales se realizaron del 30 de marzo de 2026 al 29 de junio de 2026, con una dedicación conforme al calendario escolar y a las necesidades operativas de la empresa receptora. A continuación se presenta una línea de tiempo resumida de los hitos principales del proyecto:")
$(Para "• 30 de marzo – 15 de abril de 2026: Levantamiento del sistema legacy, configuración del entorno Laravel, migraciones de base de datos y autenticación de técnicos.`n• 16 de abril – 30 de abril de 2026: Implementación del formulario de orden de servicio (RegistrarOrdenService), listado de órdenes y cambio de estatus con registro en bitácora.`n• 1 de mayo – 15 de mayo de 2026: Módulo de historial, generación de PDF, panel de administración (usuarios, registro, catálogo SERSOP, seguridad) y bloqueo de edición concurrente.`n• 16 de mayo – 31 de mayo de 2026: Integración WhatsApp Cloud API (notificaciones automáticas, colas en base de datos, plantillas Meta), despliegue en producción y diagnóstico de conectividad.`n• 1 de junio – 29 de junio de 2026: Módulo de chat WhatsApp en tiempo casi real, badge de mensajes pendientes en navbar, páginas legales para Meta y refinamiento de la interfaz de usuario.")
$(PageBreak)
$(Head "4.- ACTIVIDADES REALIZADAS")
$(Para "A continuación se describen las principales actividades técnicas ejecutadas durante el proyecto, organizadas por área funcional.")
$(Head "4.1 Migración de PHP legacy a Laravel")
$(Para "Se analizó el código fuente del sistema PHP anterior y se estableció una tabla de equivalencias entre rutas legacy (ordenes.php, orden_servicio.php, historial_ordenes.php, admin_destino.php, entre otras) y las nuevas rutas Laravel (/ordenes, /orden_servicio, /historial, /admin). Se configuraron variables de entorno ANLUX_* para el cutover gradual, incluyendo banner informativo para usuarios y enlace de escape hacia el sistema anterior durante la fase piloto. Se preservó la compatibilidad con la cookie «recordar» del login legacy mediante LegacyRememberMiddleware.")
$(Head "4.2 Gestión de órdenes de servicio")
$(Para "Se implementó el servicio RegistrarOrdenService, responsable de validar, persistir y auditar cada orden en las tablas orden_servicio_c, orden_servicio_t, equipos, trabajos y materiales. El formulario Blade orden_form.blade.php integra validaciones del lado cliente con JavaScript legacy (orden_servicio.js) y del lado servidor. La API POST /api/ordenes/registrar procesa altas y ediciones; GET /api/ordenes alimenta el listado dinámico con búsqueda por folio o cliente, filtros por estatus (Recepción, En proceso, Terminado, Entregado) y rango de fechas. El cambio de estatus se realiza vía POST /api/ordenes/estatus con OrdenStatusService, registrando cada cambio en orden_servicio_tecnico_log.")
$(Head "4.3 Seguridad y cifrado de datos sensibles")
$(Para "Se integró AnluxVaultService para cifrar en reposo el teléfono y domicilio del cliente mediante prefijo v1: en base de datos, utilizando secreto configurado en .env (ANLUX_TELEFONO_SECRET). Se implementaron políticas de acceso (OrdenPolicyService, OrderPolicy), middleware de cabeceras de seguridad, registro de actividad en SecurityActivityLogger y módulo de seguridad en /admin/seguridad. Se desarrolló OrdenEditLockService para evitar que dos técnicos editen la misma orden simultáneamente, con heartbeat y liberación de bloqueo vía API.")
$(Head "4.4 Generación de PDF e historial")
$(Para "Se configuró OrderPdfController para servir comprobantes en GET /pdf/orden/{id}, con opción inline para visualización en iframe. El historial en /historial reutiliza la misma API de listado mediante historial_laravel.js, permitiendo localizar órdenes anteriores y abrir su PDF sin salir del sistema. Para WhatsApp se creó una ruta firmada /wa/pdf/orden/{id} que permite a Meta descargar el documento sin subirlo manualmente al endpoint /media, optimizando el envío en hosting con conectividad limitada.")
$(Head "4.5 Notificaciones automáticas (correo y WhatsApp)")
$(Para "Se desarrolló OrderEmailService y OrderWhatsappService para notificar al cliente cuando una orden entra en estatus Recepción, Terminado o Entregado. Los envíos se procesan en segundo plano mediante SendOrderStatusEmailJob y SendOrderWhatsappJob con QUEUE_CONNECTION=database, evitando timeouts al guardar la orden. Las plantillas de WhatsApp (orden_recepcion, orden_terminado, orden_entregado) fueron configuradas en Meta Business. Se normalizan números telefónicos mexicanos (ej. 6121684390 → 526121684390) y se registra cada intento en order_whatsapp_notifications. Se crearon herramientas de diagnóstico (docheck.php, wa_test_send.php) para validar conectividad con graph.facebook.com desde el servidor de producción.")
$(Head "4.6 Módulo de soporte WhatsApp")
$(Para "Se implementó WhatsappWebhookController para recibir mensajes entrantes de Meta en /webhooks/whatsapp/cloud, persistiendo conversaciones en wa_conversations y wa_messages. La interfaz /soporte/whatsapp permite a los técnicos ver la bandeja de chats, responder mensajes y marcar conversaciones como leídas. Se añadió polling en tiempo casi real (endpoint /api/soporte/whatsapp/novedades) y un badge en el navbar (wa_nav_badge.js) que muestra cuántos clientes esperan respuesta, con barra de aviso y notificaciones toast al recibir mensajes nuevos.")
$(Head "4.7 Administración y despliegue")
$(Para "El panel /admin concentra el registro de nuevos técnicos (AdminRegistroController), gestión de usuarios con cambio de contraseña y activación/desactivación (AdminUsersController), catálogo SERSOP de tipos de servicio (CatalogoSersopController) y consulta de actividad de seguridad. Se documentó el despliegue en cPanel (OPERACION_ANLUX.md): subdominio soporte.anlux.mx, document root en public/, migraciones, worker de colas y cron para queue:work. Se publicaron páginas legales (/terminos-y-condiciones, /aviso-de-privacidad, /eliminar-datos) requeridas para la revisión de la aplicación en Meta.")
$(PageBreak)
$(Head "5.- METAS ALCANZADAS")
$(Para "Al término del periodo de prácticas se cumplieron las metas planificadas para la primera fase del proyecto Anlux Laravel. A continuación se detalla el grado de cumplimiento de cada meta:")
$(Para "• Meta 1 — Sistema operativo en Laravel: CUMPLIDA. El front principal del taller funciona en soporte.anlux.mx con login, listado, captura, historial y PDF.`n• Meta 2 — Compatibilidad con base de datos legacy: CUMPLIDA. Las órdenes históricas y la tabla login se reutilizan sin pérdida de información; los técnicos existentes acceden con sus credenciales.`n• Meta 3 — Cifrado de datos sensibles: CUMPLIDA. Teléfono y domicilio del cliente se almacenan cifrados cuando ANLUX_TELEFONO_SECRET está configurado.`n• Meta 4 — Notificaciones por correo en cola: CUMPLIDA. Los correos de cambio de estatus se encolan y no bloquean el guardado de la orden.`n• Meta 5 — Integración WhatsApp (envío automático): CUMPLIDA a nivel de software. El código, plantillas, colas y PDF firmado están listos; el envío en producción depende de que el hosting habilite salida HTTPS hacia graph.facebook.com (bloqueo de red identificado y documentado).`n• Meta 6 — Chat de soporte WhatsApp: CUMPLIDA. Los técnicos reciben y responden mensajes de clientes desde /soporte/whatsapp con actualización automática y avisos en navbar.`n• Meta 7 — Panel de administración: CUMPLIDA. Registro de técnicos, usuarios, catálogo y seguridad operativos con control de rol admin.`n• Meta 8 — Documentación operativa: CUMPLIDA. Se elaboraron OPERACION_ANLUX.md, MIGRATION_CUTOVER.md y documentos de diagnóstico WhatsApp para el equipo y el proveedor de hosting.")
$(Para "Como evidencia tangible del avance, el sistema procesó órdenes de prueba en producción (folios OS-2026-020, 021, 022 y posteriores), validando el flujo completo de registro, cambio de estatus y encolado de notificaciones. Las pruebas automatizadas de Laravel (php artisan test) se ejecutaron en el entorno de desarrollo para validar controladores críticos como WhatsappWebhookController. La interfaz de usuario fue refinada con modales propios (anluxUiModal), firma digital en canvas y estilos responsivos en las vistas principales.")
$(Para "En el ámbito formativo, la práctica permitió consolidar competencias del perfil de egreso del técnico en programación: análisis de requerimientos, modelado de datos, programación backend con PHP y Laravel, consumo e implementación de APIs REST, integración con servicios de terceros (Meta WhatsApp Cloud API), manejo de colas y jobs asíncronos, y despliegue en servidor web con restricciones de hosting compartido. Asimismo, se fortalecieron habilidades transversales como la documentación técnica, la comunicación con el asesor empresarial y la resolución sistemática de incidentes en producción.")
$(PageBreak)
$(Head "6.- CONCLUSIONES")
$(Para "La práctica profesional en Anlux constituyó una experiencia formativa de alto valor, al participar en un proyecto real de migración y modernización de un sistema de gestión que soporta la operación diaria de un taller de servicio técnico. El trabajo iniciado el 30 de marzo de 2026 evolucionó desde el análisis del sistema legacy hasta un despliegue funcional en producción, abarcando módulos de negocio, seguridad, comunicación con clientes y administración.")
$(Para "La adopción de Laravel como framework principal demostró ser una decisión acertada para la empresa: el código resultante es más modular, testeable y mantenible que el PHP procedural anterior. Los servicios dedicados (RegistrarOrdenService, OrdenListService, OrderWhatsappService, WhatsappChatService, entre otros) encapsulan la lógica de negocio y facilitan futuras extensiones sin afectar la estabilidad del sistema. La estrategia de cutover gradual, con coexistencia temporal del sistema legacy, redujo el riesgo operativo y permitió validar cada entrega con usuarios reales del taller.")
$(Para "Uno de los aprendizajes más relevantes fue la distinción entre problemas de software e infraestructura. El módulo de notificaciones WhatsApp quedó correctamente implementado en Laravel —colas, plantillas, normalización de teléfonos, PDF por enlace firmado—, pero el hosting compartido en producción no logra establecer conexión saliente con graph.facebook.com, generando timeouts (cURL error 28). Este hallazgo subraya la importancia de validar requisitos de red y firewall antes de integrar APIs externas, y de documentar con precisión las limitaciones para gestionar expectativas con el cliente interno y el proveedor de hosting.")
$(Para "El módulo de chat WhatsApp, por el contrario, opera de manera satisfactoria: los mensajes entrantes de clientes se reciben vía webhook, se almacenan en base de datos y los técnicos pueden responder desde el navegador con actualización casi en tiempo real. El badge de mensajes pendientes en el navbar mejora la atención al cliente al visibilizar de inmediato quién espera respuesta, integrando la comunicación instantánea en el flujo de trabajo del taller.")
$(Para "Se recomienda para fases posteriores del proyecto: (1) migrar el hosting a un VPS o plan que permita salida HTTPS hacia Meta, para activar el envío automático de WhatsApp; (2) continuar la sustitución progresiva de JavaScript legacy por componentes modernos; (3) ampliar la cobertura de pruebas automatizadas; (4) implementar monitoreo de colas y alertas operativas; y (5) capacitar al personal en el uso del módulo de chat y las nuevas funcionalidades del panel administrativo.")
$(Para "En conclusión, las prácticas profesionales en Anlux cumplieron su propósito de vincular la formación académica del CBTis 062 con la solución de problemas reales de la industria del software y los servicios de TI. El informe global aquí presentado refleja un periodo de tres meses de trabajo constante, con entregables concretos desplegados en soporte.anlux.mx y documentación que permitirá al equipo de la empresa continuar el desarrollo y la operación del sistema Anlux con bases técnicas sólidas.")
$(Para "")
$(Para "La Paz, Baja California Sur, a 29 de junio de 2026.")
$(Para "")
$(Para "_______________________________")
$(Para "Firma del alumno")
$(Para "")
$(Para "_______________________________")
$(Para "Firma del asesor empresarial")
$(Para "")
$(Para "_______________________________")
$(Para "Firma del asesor académico")
"@

$documentXml = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
<w:body>$body<w:sectPr><w:pgSz w:w="12240" w:h="15840"/><w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440" w:header="720" w:footer="720" w:gutter="0"/></w:sectPr></w:body>
</w:document>
"@

$stylesXml = @'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
<w:docDefaults><w:rPrDefault><w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman"/><w:sz w:val="24"/></w:rPr></w:rPrDefault></w:docDefaults>
</w:styles>
'@

$contentTypes = @'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
<Default Extension="xml" ContentType="application/xml"/>
<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.styles+xml"/>
</Types>
'@

$rels = @'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>
'@

$docRels = @'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>
'@

$utf8 = New-Object System.Text.UTF8Encoding $false
[System.IO.File]::WriteAllText("$tempDir\[Content_Types].xml", $contentTypes, $utf8)
[System.IO.File]::WriteAllText("$tempDir\_rels\.rels", $rels, $utf8)
[System.IO.File]::WriteAllText("$tempDir\word\document.xml", $documentXml, $utf8)
[System.IO.File]::WriteAllText("$tempDir\word\styles.xml", $stylesXml, $utf8)
[System.IO.File]::WriteAllText("$tempDir\word\_rels\document.xml.rels", $docRels, $utf8)

if (Test-Path $outFile) { Remove-Item -Force $outFile }
Add-Type -AssemblyName System.IO.Compression.FileSystem
[System.IO.Compression.ZipFile]::CreateFromDirectory($tempDir, $outFile)
Remove-Item -Recurse -Force $tempDir

Write-Host "Generado: $outFile"
