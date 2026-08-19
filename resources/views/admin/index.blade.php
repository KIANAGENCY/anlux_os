@extends('layouts.exacto_app')

@php
    $pageTitle = 'Panel de administrador - Exacto';
    // Evita un error 500 si durante un despliegue la vista llega antes que el controlador.
    $maintenance = $maintenance ?? [
        'enabled' => false,
        'message' => 'Estamos actualizando el sistema. Por favor, vuelve a intentarlo en unos minutos.',
        'updated_at' => null,
    ];
@endphp

@push('styles')
<style>
    .exacto-maintenance-button {
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
        opacity: 1 !important;
        visibility: visible !important;
    }
    .exacto-maintenance-button--disable {
        background: #059669 !important;
        box-shadow: 0 6px 15px rgba(5, 150, 105, .25) !important;
    }
    .exacto-maintenance-button--disable:hover { background: #047857 !important; }
    .exacto-maintenance-button--enable {
        background: #d97706 !important;
        box-shadow: 0 6px 15px rgba(217, 119, 6, .25) !important;
    }
    .exacto-maintenance-button--enable:hover { background: #b45309 !important; }
</style>
@endpush

@section('content')
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased">
    <div class="mx-auto w-full max-w-4xl px-4 py-6 sm:py-8">
        <div class="overflow-hidden rounded-2xl bg-white shadow-xl ring-1 ring-slate-200/80">
            {{-- Encabezado --}}
            <header class="border-b border-slate-200 bg-gradient-to-br from-blue-800 via-blue-700 to-blue-600 px-5 py-6 sm:px-8">
                <div class="flex flex-col items-center gap-5 text-center sm:flex-row sm:text-left">
                    <img
                        src="{{ asset('legacy/public/img/logo.jpeg') }}?v={{ @filemtime(public_path('legacy/public/img/logo.jpeg')) ?: 1 }}"
                        alt="Exacto"
                        class="h-14 w-auto rounded-lg bg-white/95 p-2 shadow-md sm:h-16"
                        width="180"
                        height="60"
                        style="max-width: 180px; object-fit: contain;"
                    >
                    <div class="text-white">
                        <p class="text-xs font-semibold uppercase tracking-widest text-blue-200">Exacto &middot; Administraci&oacute;n</p>
                        <h1 class="mt-1 text-2xl font-bold sm:text-3xl">Panel de administrador</h1>
                        <p class="mt-2 max-w-xl text-sm text-blue-100">
                            Selecciona una secci&oacute;n para gestionar &oacute;rdenes, usuarios, cat&aacute;logo y seguridad.
                        </p>
                    </div>
                </div>
            </header>

            <div class="px-4 py-5 sm:px-6 sm:py-6">
                @include('partials.nav-admin')

                @if(session('status'))
                    <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif

                <section class="mt-6 rounded-xl border-2 {{ $maintenance['enabled'] ? 'border-amber-400 bg-amber-50' : 'border-slate-200 bg-slate-50' }} p-5 shadow-sm">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <div class="flex items-center gap-3">
                                <span class="flex h-11 w-11 items-center justify-center rounded-xl {{ $maintenance['enabled'] ? 'bg-amber-500 text-white' : 'bg-slate-200 text-slate-600' }}">
                                    <i class="fas fa-screwdriver-wrench text-lg" aria-hidden="true"></i>
                                </span>
                                <div>
                                    <h2 class="text-lg font-extrabold text-slate-900">Modo de mantenimiento</h2>
                                    <p class="text-sm font-semibold {{ $maintenance['enabled'] ? 'text-amber-800' : 'text-slate-500' }}">
                                        {{ $maintenance['enabled'] ? 'ACTIVO: los usuarios no administradores están bloqueados.' : 'INACTIVO: el sistema está disponible.' }}
                                    </p>
                                </div>
                            </div>
                            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                                Actívalo antes de subir, sustituir o eliminar archivos. Los administradores podrán seguir usando este panel para desactivarlo al terminar.
                            </p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.maintenance.update') }}" class="mt-5">
                        @csrf
                        <label for="maintenanceMessage" class="mb-2 block text-sm font-bold text-slate-700">Mensaje para los usuarios</label>
                        <textarea id="maintenanceMessage" name="message" rows="3" maxlength="500" class="w-full rounded-lg border-2 border-slate-300 bg-white px-4 py-3 text-sm focus:border-blue-600 focus:outline-none">{{ old('message', $maintenance['message']) }}</textarea>
                        @error('message')
                            <p class="mt-1 text-sm font-semibold text-red-600">{{ $message }}</p>
                        @enderror

                        <div class="mt-4 flex flex-wrap gap-3">
                            @if($maintenance['enabled'])
                                <button type="submit" name="enabled" value="0" class="exacto-maintenance-button exacto-maintenance-button--disable">
                                    <i class="fas fa-play" aria-hidden="true"></i>
                                    Desactivar mantenimiento
                                </button>
                            @else
                                <button type="submit" name="enabled" value="1" class="exacto-maintenance-button exacto-maintenance-button--enable">
                                    <i class="fas fa-pause" aria-hidden="true"></i>
                                    Activar mantenimiento
                                </button>
                            @endif
                        </div>
                    </form>
                </section>

                <div class="mt-6 grid gap-3 sm:grid-cols-2">
                    <a
                        href="{{ route('orden_servicio.create') }}"
                        class="group flex gap-4 rounded-xl border border-slate-200 bg-slate-50/50 p-4 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-blue-300 hover:bg-white hover:shadow-md"
                    >
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-700 transition group-hover:bg-blue-600 group-hover:text-white">
                            <i class="fas fa-clipboard-list text-xl" aria-hidden="true"></i>
                        </span>
                        <span class="min-w-0">
                            <span class="block font-bold text-slate-900 group-hover:text-blue-800">&Oacute;rdenes de servicio</span>
                            <span class="mt-1 block text-sm leading-snug text-slate-600">Registrar y editar &oacute;rdenes t&eacute;cnicas.</span>
                        </span>
                    </a>

                    <a
                        href="{{ route('admin.catalogo.index') }}"
                        class="group flex gap-4 rounded-xl border border-slate-200 bg-slate-50/50 p-4 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-emerald-300 hover:bg-white hover:shadow-md"
                    >
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 transition group-hover:bg-emerald-600 group-hover:text-white">
                            <i class="fas fa-list text-xl" aria-hidden="true"></i>
                        </span>
                        <span class="min-w-0">
                            <span class="block font-bold text-slate-900 group-hover:text-emerald-800">Cat&aacute;logo SERSOP</span>
                            <span class="mt-1 block text-sm leading-snug text-slate-600">Claves, precios y condiciones del PDF.</span>
                        </span>
                    </a>

                    <a
                        href="{{ route('admin.registro.create') }}"
                        class="group flex gap-4 rounded-xl border border-slate-200 bg-slate-50/50 p-4 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-cyan-300 hover:bg-white hover:shadow-md"
                    >
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-cyan-100 text-cyan-800 transition group-hover:bg-cyan-600 group-hover:text-white">
                            <i class="fas fa-user-plus text-xl" aria-hidden="true"></i>
                        </span>
                        <span class="min-w-0">
                            <span class="block font-bold text-slate-900 group-hover:text-cyan-800">Registro de usuarios</span>
                            <span class="mt-1 block text-sm leading-snug text-slate-600">Dar de alta nuevos t&eacute;cnicos o administradores.</span>
                        </span>
                    </a>

                    <a
                        href="{{ route('admin.users.index') }}"
                        class="group flex gap-4 rounded-xl border border-slate-200 bg-slate-50/50 p-4 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-amber-300 hover:bg-white hover:shadow-md"
                    >
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-800 transition group-hover:bg-amber-500 group-hover:text-white">
                            <i class="fas fa-users text-xl" aria-hidden="true"></i>
                        </span>
                        <span class="min-w-0">
                            <span class="block font-bold text-slate-900 group-hover:text-amber-900">Tabla de usuarios</span>
                            <span class="mt-1 block text-sm leading-snug text-slate-600">Consultar y actualizar cuentas existentes.</span>
                        </span>
                    </a>

                    <a
                        href="{{ route('admin.folios.index') }}"
                        class="group flex gap-4 rounded-xl border border-slate-200 bg-slate-50/50 p-4 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-orange-300 hover:bg-white hover:shadow-md"
                    >
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-orange-100 text-orange-800 transition group-hover:bg-orange-500 group-hover:text-white">
                            <i class="fas fa-hashtag text-xl" aria-hidden="true"></i>
                        </span>
                        <span class="min-w-0">
                            <span class="block font-bold text-slate-900 group-hover:text-orange-900">Folios</span>
                            <span class="mt-1 block text-sm leading-snug text-slate-600">Huecos liberados y contador de folio OS-a&ntilde;o.</span>
                        </span>
                    </a>

                    <a
                        href="{{ route('admin.seguridad.index') }}"
                        class="group flex gap-4 rounded-xl border border-slate-200 bg-slate-50/50 p-4 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-red-300 hover:bg-white hover:shadow-md"
                    >
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-700 transition group-hover:bg-red-600 group-hover:text-white">
                            <i class="fas fa-shield-alt text-xl" aria-hidden="true"></i>
                        </span>
                        <span class="min-w-0">
                            <span class="block font-bold text-slate-900 group-hover:text-red-800">Seguridad / Actividad</span>
                            <span class="mt-1 block text-sm leading-snug text-slate-600">Eventos, alertas y estado del cifrado.</span>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
@endsection
