# ANLUX — Copia archivos del plan a deploy_subir_servidor/ (listo para cPanel)
# Ejecutar en PowerShell desde la raíz del proyecto:
#   .\database\scripts\preparar_subida_servidor.ps1

$ErrorActionPreference = 'Stop'
$root = Split-Path (Split-Path $PSScriptRoot -Parent) -Parent
if (-not (Test-Path (Join-Path $root 'artisan'))) {
    $root = (Get-Location).Path
}
if (-not (Test-Path (Join-Path $root 'artisan'))) {
    Write-Error "No se encontró artisan. Ejecuta desde c:\laragon\www\anlux_laravel o ajusta `$root."
}

$dest = Join-Path $root 'deploy_subir_servidor'
$manifest = Join-Path $dest 'LISTA_ARCHIVOS.txt'

$archivos = @(
    'routes\web.php',
    'app\Providers\AppServiceProvider.php',
    'app\Services\IntegrationSettingsService.php',
    'app\Services\BrandingService.php',
    'app\Services\SecurityActivityLogger.php',
    'app\Http\Controllers\AdminIntegrationsController.php',
    'app\Http\Controllers\AdminAppearanceController.php',
    'app\Http\Controllers\BrandingAssetController.php',
    'app\Http\Middleware\EnsureApplicationIsAvailable.php',
    'app\Mail\OrderStatusMail.php',
    'app\Jobs\SendOrderWhatsappJob.php',
    'resources\views\admin\integraciones.blade.php',
    'resources\views\admin\apariencia.blade.php',
    'resources\views\admin\index.blade.php',
    'resources\views\layouts\anlux_app.blade.php',
    'resources\views\layouts\anlux_guest.blade.php',
    'resources\views\layouts\legal.blade.php',
    'resources\views\partials\anlux-brand-head.blade.php',
    'resources\views\partials\nav-admin.blade.php',
    'resources\views\partials\nav-app.blade.php',
    'resources\views\emails\orders\status.blade.php',
    'resources\views\auth\login.blade.php',
    'resources\views\index.blade.php',
    'resources\views\maintenance.blade.php',
    'public\build',
    'public\legacy\assets\js\orden_servicio.js',
    'public\legacy\js\historial_laravel.js',
    'public\legacy\js\ordenes_laravel.js',
    'resources\views\orders\historial_page.blade.php',
    'resources\views\orders\orden_form.blade.php',
    'app\Support\OrderStatus.php',
    'app\Support\TipoServicioCatalog.php',
    'app\Support\OrdenObservacionesValidator.php',
    'app\Support\OrdenComentariosValidator.php',
    'app\Services\OrdenListService.php',
    'app\Services\RegistrarOrdenService.php',
    'app\Http\Controllers\OrderFormController.php',
    'app\Http\Controllers\OrderPdfController.php',
    'app\Services\OrderEmailService.php',
    'app\Jobs\SendOrderStatusEmailJob.php',
    'app\Services\OrdenPolicyService.php',
    'app\Support\AnluxAuthContext.php',
    'app\Services\AnluxVaultService.php',
    'app\Services\OrdenStatusService.php',
    'app\Models\User.php'
)

if (Test-Path $dest) {
    Remove-Item $dest -Recurse -Force
}
New-Item -ItemType Directory -Path $dest -Force | Out-Null

$ok = @()
$faltan = @()

foreach ($rel in $archivos) {
    $src = Join-Path $root $rel
    $out = Join-Path $dest $rel
    if (-not (Test-Path $src)) {
        $faltan += $rel
        continue
    }
    $dir = Split-Path $out -Parent
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
    }
    if (Test-Path $src -PathType Container) {
        Copy-Item $src $out -Recurse -Force
    } else {
        Copy-Item $src $out -Force
    }
    $ok += $rel
}

$lines = @(
    'ANLUX — Archivos para subir al servidor',
    "Generado: $(Get-Date -Format 'yyyy-MM-dd HH:mm')",
    "Origen: $root",
    "Carpeta empaquetada: $dest",
    '',
    'INSTRUCCIONES',
    '-----------',
    '1. Abre deploy_subir_servidor en el Explorador de archivos.',
    '2. Sube cada carpeta (app, public, resources) dentro de tu proyecto Laravel en cPanel,',
    '   manteniendo la misma ruta (fusionar/reemplazar archivos).',
    '3. O comprime deploy_subir_servidor en .zip y extrae en la raíz del sitio.',
    '4. Borra public/docheck.php en el servidor si existe.',
    '5. Ctrl+F5 en /ordenes, /historial y al editar una orden.',
    '',
    "COPIADOS ($($ok.Count))",
    '--------'
) + $ok

if ($faltan.Count -gt 0) {
    $lines += '', "NO ENCONTRADOS ($($faltan.Count))", '----------------'
    $lines += $faltan
}

$lines | Set-Content -Path $manifest -Encoding UTF8

Write-Host ''
Write-Host 'Listo.' -ForegroundColor Green
Write-Host "Carpeta: $dest"
Write-Host "Lista:   $manifest"
Write-Host "Archivos copiados: $($ok.Count)"
if ($faltan.Count -gt 0) {
    Write-Host "Faltantes: $($faltan.Count)" -ForegroundColor Yellow
}
