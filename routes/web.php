<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminFoliosController;
use App\Http\Controllers\AdminRegistroController;
use App\Http\Controllers\AdminUsersController;
use App\Http\Controllers\CatalogoSersopController;
use App\Http\Controllers\HistorialOrdenesController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderEditLockController;
use App\Http\Controllers\OrderFormController;
use App\Http\Controllers\OrderPdfController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SeguridadController;
use App\Http\Controllers\WhatsappWebhookController;
use App\Http\Middleware\ExactoUpdatePresence;
use App\Http\Middleware\LegacyRememberMiddleware;
use App\Services\OrdenPolicyService;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $user = Auth::user();
    $isAdmin = $user ? app(OrdenPolicyService::class)->userIsAdmin($user) : false;

    return view('index', [
        'pageTitle' => 'Inicio - Exacto',
        'user' => $user,
        'isAdmin' => $isAdmin,
    ]);
})->name('home');

Route::get('/terminos-y-condiciones', [LegalController::class, 'terminos'])->name('legal.terminos');
Route::get('/aviso-de-privacidad', [LegalController::class, 'privacidad'])->name('legal.privacidad');
Route::get('/eliminar-datos', [LegalController::class, 'eliminarDatos'])->name('legal.eliminar-datos');

if (config('exacto.catalogo_sersop_guest')) {
    Route::get('/admin/catalogo-sersop', [CatalogoSersopController::class, 'index'])->name('admin.catalogo.index');
    Route::post('/admin/catalogo-sersop', [CatalogoSersopController::class, 'update'])->name('admin.catalogo.update');
    Route::post('/admin/catalogo-sersop/sync-precios-sin-iva', [CatalogoSersopController::class, 'syncPreciosSinIva'])->name('admin.catalogo.syncPreciosSinIva');
}

Route::get('/dashboard', function () {
    return redirect()->route('ordenes.index');
})->middleware(['auth'])->name('dashboard');

Route::get('/webhooks/whatsapp/cloud', [WhatsappWebhookController::class, 'verify'])
    ->withoutMiddleware([VerifyCsrfToken::class, LegacyRememberMiddleware::class, ExactoUpdatePresence::class])
    ->name('webhooks.whatsapp.verify');
Route::post('/webhooks/whatsapp/cloud', [WhatsappWebhookController::class, 'receive'])
    ->withoutMiddleware([VerifyCsrfToken::class, LegacyRememberMiddleware::class, ExactoUpdatePresence::class])
    ->name('webhooks.whatsapp.receive');

Route::get('/wa/pdf/orden/{id}', [OrderPdfController::class, 'showSigned'])
    ->middleware('signed')
    ->whereNumber('id')
    ->name('pdf.orden.wa');

Route::middleware('auth')->group(function () {
    Route::get('/mantenimiento', [MaintenanceController::class, 'show'])->name('maintenance.show');

    Route::get('/ordenes', [OrderController::class, 'index'])->name('ordenes.index');
    Route::get('/api/ordenes', [OrderController::class, 'list'])->name('ordenes.list');
    Route::get('/api/ordenes/{id}/equipos-entregados', [OrderController::class, 'equiposEntregados'])->whereNumber('id')->name('ordenes.equiposEntregados');
    Route::post('/api/ordenes/estatus', [OrderController::class, 'updateStatus'])->name('ordenes.updateStatus');
    Route::post('/api/ordenes/registrar', [OrderController::class, 'registrar'])->name('orders.registrar');
    Route::post('/api/ordenes/{id}/salida-temporal', [OrderController::class, 'salidaTemporal'])->whereNumber('id')->name('ordenes.salidaTemporal');
    Route::post('/api/ordenes/{id}/regreso-temporal', [OrderController::class, 'regresoTemporal'])->whereNumber('id')->name('ordenes.regresoTemporal');
    Route::get('/api/ordenes/whatsapp-estado/{id}', [OrderController::class, 'whatsappEstado'])->whereNumber('id')->name('ordenes.whatsappEstado');
    Route::post('/api/ordenes/{id}/reenviar', [OrderController::class, 'reenviar'])->whereNumber('id')->name('ordenes.reenviar');
    Route::post('/api/ordenes/{id}/lock/heartbeat', [OrderEditLockController::class, 'heartbeat'])->whereNumber('id')->name('ordenes.lock.heartbeat');
    Route::post('/api/ordenes/{id}/lock/release', [OrderEditLockController::class, 'release'])->whereNumber('id')->name('ordenes.lock.release');

    Route::post('/api/ordenes/validar-saldo', [OrderController::class, 'validarSaldo'])->name('ordenes.validarSaldo');
    Route::post('/api/ordenes/liquidar-saldo-equipo', [OrderController::class, 'liquidarSaldoEquipo'])->name('ordenes.liquidarSaldoEquipo');

    Route::get('/api/impersonacion/cuentas', [ImpersonationController::class, 'cuentas'])->name('impersonacion.cuentas');
    Route::get('/api/usuarios/tecnicos-activos', [ImpersonationController::class, 'tecnicosActivos'])->name('impersonacion.tecnicos');
    Route::post('/api/impersonacion/solicitar', [ImpersonationController::class, 'solicitar'])->name('impersonacion.solicitar');
    Route::get('/api/impersonacion/estado/{token}', [ImpersonationController::class, 'estado'])->name('impersonacion.estado');
    Route::post('/api/impersonacion/aplicar', [ImpersonationController::class, 'aplicar'])->name('impersonacion.aplicar');
    Route::post('/api/impersonacion/cancelar', [ImpersonationController::class, 'cancelar'])->name('impersonacion.cancelar');
    Route::get('/api/impersonacion/pendientes', [ImpersonationController::class, 'pendientes'])->name('impersonacion.pendientes');
    Route::post('/api/impersonacion/responder', [ImpersonationController::class, 'responder'])->name('impersonacion.responder');
    Route::post('/api/impersonacion/salir', [ImpersonationController::class, 'salir'])->name('impersonacion.salir');

    Route::get('/orden_servicio', [OrderFormController::class, 'create'])->name('orden_servicio.create');
    Route::get('/orden_servicio/{id}', [OrderFormController::class, 'edit'])
        ->whereNumber('id')
        ->name('orden_servicio.edit');

    Route::get('/pdf/orden/{id}', [OrderPdfController::class, 'show'])
        ->whereNumber('id')
        ->name('pdf.orden');

    Route::get('/historial', [HistorialOrdenesController::class, 'index'])->name('historial.index');

    Route::get('/admin', [AdminController::class, 'index'])->middleware('exacto.admin')->name('admin.index');
    Route::post('/admin/mantenimiento', [AdminController::class, 'updateMaintenance'])->middleware('exacto.admin')->name('admin.maintenance.update');
    Route::get('/admin/registro', [AdminRegistroController::class, 'create'])->middleware('exacto.admin')->name('admin.registro.create');
    Route::post('/admin/registro', [AdminRegistroController::class, 'store'])->middleware('exacto.admin')->name('admin.registro.store');
    Route::get('/admin/usuarios', [AdminUsersController::class, 'index'])->middleware('exacto.admin')->name('admin.users.index');
    Route::post('/admin/usuarios/{id}/password', [AdminUsersController::class, 'updatePassword'])->middleware('exacto.admin')->name('admin.users.updatePassword');
    Route::patch('/admin/usuarios/{id}/activo', [AdminUsersController::class, 'toggleActivo'])->middleware('exacto.admin')->name('admin.users.toggleActivo');
    Route::delete('/admin/usuarios/{id}', [AdminUsersController::class, 'destroy'])->middleware('exacto.admin')->whereNumber('id')->name('admin.users.destroy');
    if (! config('exacto.catalogo_sersop_guest')) {
        Route::get('/admin/catalogo-sersop', [CatalogoSersopController::class, 'index'])->middleware('exacto.admin')->name('admin.catalogo.index');
        Route::post('/admin/catalogo-sersop', [CatalogoSersopController::class, 'update'])->middleware('exacto.admin')->name('admin.catalogo.update');
        Route::post('/admin/catalogo-sersop/sync-precios-sin-iva', [CatalogoSersopController::class, 'syncPreciosSinIva'])->middleware('exacto.admin')->name('admin.catalogo.syncPreciosSinIva');
    }
    Route::get('/admin/seguridad', [SeguridadController::class, 'index'])->middleware('exacto.admin')->name('admin.seguridad.index');
    Route::get('/admin/folios', [AdminFoliosController::class, 'index'])->middleware('exacto.admin')->name('admin.folios.index');
    Route::post('/admin/folios/sync', [AdminFoliosController::class, 'sync'])->middleware('exacto.admin')->name('admin.folios.sync');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
