@extends('layouts.anlux_app')

@php
    $pageTitle = 'Panel de administrador - Anlux';
    $nav_admin_activo = 'admin';
    $maintenance = $maintenance ?? [
        'enabled' => false,
        'message' => 'Estamos actualizando el sistema. Por favor, vuelve a intentarlo en unos minutos.',
        'updated_at' => null,
    ];
    $logoUrl = $anluxLogoUrl ?? asset('legacy/public/img/logo.jpeg');
@endphp

@push('styles')
<style>
    .anlux-maintenance-button {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 9px !important;
        min-height: 46px !important;
        padding: 0 22px !important;
        border: 0 !important;
        border-radius: 10px !important;
        color: #ffffff !important;
        font-weight: 700 !important;
        cursor: pointer !important;
    }
    .anlux-maintenance-button--disable { background: #059669 !important; }
    .anlux-maintenance-button--enable { background: #d97706 !important; }
</style>
@vite(['resources/js/admin/index/main.tsx'])
@endpush

@section('content')
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased">
    <div class="mx-auto w-full max-w-6xl px-4 py-6 sm:py-8">
        @include('partials.nav-admin')

        <div id="admin-index-react-root">
            {{-- Fallback operativo para despliegues parciales; React lo reemplaza al montar. --}}
            <section class="anlux-page-card">
                <header class="anlux-page-header">
                    <div>
                        <p class="anlux-eyebrow">Anlux &middot; Administraci&oacute;n</p>
                        <h1 class="anlux-page-title">Panel de administrador</h1>
                        <p class="anlux-page-description">Controla el mantenimiento y abre las &aacute;reas administrativas del sistema.</p>
                    </div>
                </header>
                <div class="p-4 sm:p-6">
                    @if(session('status'))
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>
                    @endif
                    <section class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-5 shadow-sm">
                        <h2 class="text-lg font-extrabold text-slate-900">Modo de mantenimiento</h2>
                        @if($maintenance['enabled'])
                            <p class="mt-1 text-sm font-semibold text-amber-800">ACTIVO: los usuarios no administradores están bloqueados.</p>
                        @else
                            <p class="mt-1 text-sm font-semibold text-slate-600">INACTIVO: el sistema está disponible.</p>
                        @endif
                        <form method="POST" action="{{ route('admin.maintenance.update') }}" class="mt-5">
                            @csrf
                            <label for="maintenanceMessageFallback" class="anlux-label">Mensaje para los usuarios</label>
                            <textarea id="maintenanceMessageFallback" name="message" rows="3" maxlength="500" class="anlux-control w-full py-3">{{ old('message', $maintenance['message']) }}</textarea>
                            @error('message')
                                <p class="mt-1 text-sm font-semibold text-red-600">{{ $message }}</p>
                            @enderror
                            <div class="mt-4 flex justify-end">
                                <button type="submit" name="enabled" value="{{ $maintenance['enabled'] ? '0' : '1' }}" class="anlux-maintenance-button {{ $maintenance['enabled'] ? 'anlux-maintenance-button--disable' : 'anlux-maintenance-button--enable' }}">
                                    <i class="fas {{ $maintenance['enabled'] ? 'fa-play' : 'fa-pause' }}" aria-hidden="true"></i>
                                    {{ $maintenance['enabled'] ? 'Desactivar mantenimiento' : 'Activar mantenimiento' }}
                                </button>
                            </div>
                        </form>
                    </section>
                    <nav class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3" aria-label="Destinos administrativos">
                        <a href="{{ route('orden_servicio.create') }}" class="rounded-xl border border-slate-200 bg-white p-4 font-semibold text-blue-800 shadow-sm">&Oacute;rdenes de servicio</a>
                        <a href="{{ route('admin.catalogo.index') }}" class="rounded-xl border border-slate-200 bg-white p-4 font-semibold text-blue-800 shadow-sm">Cat&aacute;logo SERSOP</a>
                        <a href="{{ route('admin.registro.create') }}" class="rounded-xl border border-slate-200 bg-white p-4 font-semibold text-blue-800 shadow-sm">Registro de usuarios</a>
                        <a href="{{ route('admin.users.index') }}" class="rounded-xl border border-slate-200 bg-white p-4 font-semibold text-blue-800 shadow-sm">Tabla de usuarios</a>
                        <a href="{{ route('admin.folios.index') }}" class="rounded-xl border border-slate-200 bg-white p-4 font-semibold text-blue-800 shadow-sm">Folios</a>
                        <a href="{{ route('admin.integrations.index') }}" class="rounded-xl border border-slate-200 bg-white p-4 font-semibold text-blue-800 shadow-sm">Dominio y comunicaciones</a>
                        <a href="{{ route('admin.appearance.index') }}" class="rounded-xl border border-slate-200 bg-white p-4 font-semibold text-blue-800 shadow-sm">Apariencia</a>
                        <a href="{{ route('admin.seguridad.index') }}" class="rounded-xl border border-slate-200 bg-white p-4 font-semibold text-blue-800 shadow-sm">Seguridad / Actividad</a>
                    </nav>
                </div>
            </section>
        </div>
        <script type="application/json" id="react-page-props">
            {!! json_encode([
                'logoUrl' => $logoUrl,
                'status' => session('status'),
                'maintenance' => [
                    'enabled' => (bool) ($maintenance['enabled'] ?? false),
                    'message' => (string) ($maintenance['message'] ?? ''),
                ],
                'maintenanceAction' => route('admin.maintenance.update'),
                'csrf' => csrf_token(),
                'links' => [
                    ['href' => route('orden_servicio.create'), 'title' => 'Órdenes de servicio', 'desc' => 'Registrar y editar órdenes técnicas.', 'icon' => 'fa-clipboard-list', 'color' => 'blue'],
                    ['href' => route('admin.catalogo.index'), 'title' => 'Catálogo SERSOP', 'desc' => 'Claves, precios y condiciones del PDF.', 'icon' => 'fa-list', 'color' => 'emerald'],
                    ['href' => route('admin.registro.create'), 'title' => 'Registro de usuarios', 'desc' => 'Dar de alta nuevos técnicos o administradores.', 'icon' => 'fa-user-plus', 'color' => 'cyan'],
                    ['href' => route('admin.users.index'), 'title' => 'Tabla de usuarios', 'desc' => 'Consultar y actualizar cuentas existentes.', 'icon' => 'fa-users', 'color' => 'amber'],
                    ['href' => route('admin.folios.index'), 'title' => 'Folios', 'desc' => 'Huecos liberados y contador de folio OS-año.', 'icon' => 'fa-hashtag', 'color' => 'orange'],
                    ['href' => route('admin.integrations.index'), 'title' => 'Dominio y comunicaciones', 'desc' => 'Configurar dominio, correo SMTP y WhatsApp Cloud.', 'icon' => 'fa-plug', 'color' => 'violet'],
                    ['href' => route('admin.appearance.index'), 'title' => 'Apariencia', 'desc' => 'Cambiar paleta, tipografía y logo del sistema.', 'icon' => 'fa-palette', 'color' => 'pink'],
                    ['href' => route('admin.seguridad.index'), 'title' => 'Seguridad / Actividad', 'desc' => 'Eventos, alertas y estado del cifrado.', 'icon' => 'fa-shield-alt', 'color' => 'red'],
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
        </script>
    </div>
</body>
@endsection
