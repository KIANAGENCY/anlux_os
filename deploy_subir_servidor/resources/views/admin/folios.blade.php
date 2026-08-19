@extends('layouts.exacto_app')

@php
    $pageTitle = $pageTitle ?? 'Folios de órdenes - Exacto';
    $status = $status ?? ['anio' => (int) date('Y'), 'next_num' => 1, 'max_usado' => 0, 'proximo_folio' => '', 'huecos' => []];
    $anio = (int) ($anio ?? date('Y'));
@endphp

@section('content')
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased">
    <div class="mx-auto w-full max-w-4xl px-4 py-6 sm:py-8">
        <div class="overflow-hidden rounded-2xl bg-white shadow-xl ring-1 ring-slate-200/80">
            <header class="border-b border-slate-200 bg-gradient-to-br from-blue-800 via-blue-700 to-blue-600 px-5 py-6 sm:px-8">
                <div class="text-white">
                    <p class="text-xs font-semibold uppercase tracking-widest text-blue-200">Exacto &middot; Administraci&oacute;n</p>
                    <h1 class="mt-1 text-2xl font-bold sm:text-3xl">Folios de &oacute;rdenes</h1>
                    <p class="mt-2 max-w-xl text-sm text-blue-100">
                        Consulta huecos liberados (p.ej. &oacute;rdenes de prueba borradas) y sincroniza el contador.
                        La pr&oacute;xima orden nueva reutilizar&aacute; el menor hueco libre.
                    </p>
                </div>
            </header>

            <div class="px-4 py-5 sm:px-6 sm:py-6">
                @include('partials.nav-admin')

                @if (session('success'))
                    <div class="mt-4 rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                        {{ session('success') }}
                    </div>
                @endif

                <form method="get" action="{{ route('admin.folios.index') }}" class="mt-6 flex flex-wrap items-end gap-3">
                    <div>
                        <label for="anio" class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-600">A&ntilde;o</label>
                        <input type="number" name="anio" id="anio" value="{{ $anio }}" min="2000" max="2100"
                               class="w-28 rounded-lg border-2 border-slate-300 px-3 py-2 text-sm font-semibold">
                    </div>
                    <button type="submit" class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-bold text-white hover:bg-blue-800">
                        Ver
                    </button>
                </form>

                <div class="mt-6 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase text-slate-500">Contador next_num</p>
                        <p class="mt-1 text-2xl font-bold text-slate-900">{{ (int) $status['next_num'] }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase text-slate-500">M&aacute;ximo usado</p>
                        <p class="mt-1 text-2xl font-bold text-slate-900">{{ (int) $status['max_usado'] }}</p>
                    </div>
                    <div class="rounded-xl border border-orange-200 bg-orange-50 p-4 sm:col-span-2">
                        <p class="text-xs font-bold uppercase text-orange-800">Pr&oacute;ximo folio a asignar</p>
                        <p class="mt-1 text-2xl font-bold text-orange-950">{{ $status['proximo_folio'] }}</p>
                        <p class="mt-1 text-sm text-orange-900">Si hay huecos, se usa el menor (ej. OS-{{ $anio }}-003) sin renumerar las &oacute;rdenes existentes.</p>
                    </div>
                </div>

                <div class="mt-6">
                    <h2 class="text-lg font-bold text-slate-900">Huecos libres ({{ count($status['huecos']) }})</h2>
                    @if (count($status['huecos']) === 0)
                        <p class="mt-2 text-sm text-slate-600">No hay huecos en este a&ntilde;o. La secuencia est&aacute; continua.</p>
                    @else
                        <ul class="mt-3 flex flex-wrap gap-2">
                            @foreach ($status['huecos'] as $hueco)
                                <li class="rounded-full border border-amber-300 bg-amber-50 px-3 py-1 text-sm font-bold text-amber-950">{{ $hueco }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <form method="post" action="{{ route('admin.folios.sync') }}" class="mt-8 border-t border-slate-200 pt-6"
                      onsubmit="return confirm('¿Sincronizar el contador next_num con max(usados)+1 para {{ $anio }}?');">
                    @csrf
                    <input type="hidden" name="anio" value="{{ $anio }}">
                    <p class="mb-3 text-sm text-slate-600">
                        Usa esto si el contador qued&oacute; desfasado tras borrar &oacute;rdenes en la base de datos.
                        No cambia folios de &oacute;rdenes existentes.
                    </p>
                    <button type="submit" class="rounded-lg border-2 border-blue-600 bg-white px-4 py-2.5 text-sm font-bold text-blue-800 hover:bg-blue-50">
                        <i class="fas fa-sync-alt mr-2"></i>Sincronizar contador
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
@endsection
