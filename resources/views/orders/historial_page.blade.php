@extends('layouts.exacto_app')

@section('content')
<body class="bg-blue-50 px-3 py-4 sm:p-6 lg:p-8">
    <div class="mx-auto max-w-7xl rounded-lg bg-white p-4 shadow-lg sm:p-6 lg:p-8">
        @include('partials.nav-app')

        @include('partials.header-flujo-tres')

        <section class="mb-2 mt-2 rounded-r-lg border-l-4 border-blue-700 bg-blue-50 p-4 sm:pl-6">
            <h2 class="mb-4 flex items-center text-xl font-bold text-blue-900 sm:text-2xl">
                <i class="mr-3 text-blue-700 fas fa-search"></i>B&Uacute;SQUEDA Y FILTROS
            </h2>
            <div class="flex flex-col gap-3 md:flex-row">
                <input id="search" type="search" placeholder="Folio o nombre de cliente..." class="flex-1 px-4 py-2 border-2 border-blue-300 rounded-lg focus:outline-none focus:border-blue-700 focus:bg-blue-50">
                <button type="button" id="buscar" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-search"></i>
                </button>
                <button type="button" id="filtroBtn" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-filter"></i>
                </button>
            </div>
        </section>

        <section class="mt-2 rounded-r-lg border-l-4 border-blue-600 bg-blue-50 p-4 sm:pl-6">
            <h2 class="mb-6 flex items-center text-xl font-bold text-blue-900 sm:text-2xl">
                <i class="mr-3 text-blue-600 fas fa-list"></i>Historial de &oacute;rdenes
            </h2>
            <p class="mb-3 text-sm text-blue-900">Abre el PDF completo o despliega <strong class="font-semibold text-emerald-700">Equipos entregados</strong> para consultar receptor, fecha y PDF individual.</p>
            <div class="overflow-x-auto rounded-lg border border-blue-100">
                <table class="w-full border-collapse text-sm" style="min-width: 720px;">
                    <thead>
                        <tr class="text-white bg-blue-600">
                            <th class="p-3 text-left border">FOLIO</th>
                            <th class="p-3 text-left border">CLIENTE</th>
                            <th class="p-3 text-left border">ENTRADA</th>
                            <th class="p-3 text-left border">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span>ESTATUS</span>
                                    <button type="button" id="btnSortEstatus" class="inline-flex items-center justify-center rounded-md border border-white/40 bg-white/10 px-2 py-1 text-xs font-semibold text-white hover:bg-white/20" title="Ordenar por flujo de estatus (Recepci&oacute;n &rarr; Entregado) o por fecha de entrada">
                                        <i class="fas fa-sort" id="iconSortEstatusFecha"></i>
                                        <i class="fas fa-sort-amount-down hidden" id="iconSortEstatusFlujo"></i>
                                    </button>
                                </div>
                            </th>
                            <th class="p-3 text-center border">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody id="tabla">
                        <tr id="tablaCargando"><td class="p-3 border text-center text-slate-600" colspan="5">Cargando &oacute;rdenes&hellip;</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div id="modalFiltro" class="hidden fixed inset-0 bg-black bg-opacity-50 justify-center items-center z-50 p-3">
        <div class="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-lg bg-white p-5 shadow-2xl sm:p-8">
            <h3 class="mb-6 flex items-center text-xl font-bold text-blue-700 sm:text-2xl">
                <i class="mr-3 fas fa-filter"></i>Filtrar &oacute;rdenes
            </h3>
            <div class="mb-4">
                <label class="block mb-2 text-sm font-semibold text-blue-900">Desde:</label>
                <input type="date" id="fechaInicio" class="w-full px-4 py-2 border-2 border-blue-300 rounded-lg focus:outline-none focus:border-blue-700">
            </div>
            <div class="mb-4">
                <label class="block mb-2 text-sm font-semibold text-blue-900">Hasta:</label>
                <input type="date" id="fechaFin" class="w-full px-4 py-2 border-2 border-blue-300 rounded-lg focus:outline-none focus:border-blue-700">
            </div>
            <div class="mb-6 p-4 bg-blue-50 rounded-lg">
                <label class="block mb-2"><input type="radio" name="filtro" value="todos" checked> Todos</label>
                <label class="block mb-2"><input type="radio" name="filtro" value="rojo"> Recepci&oacute;n</label>
                <label class="block mb-2"><input type="radio" name="filtro" value="naranja"> En proceso</label>
                <label class="block mb-2"><input type="radio" name="filtro" value="amarillo"> Terminado</label>
                <label class="block"><input type="radio" name="filtro" value="verde"> Entregado</label>
            </div>
            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="button" id="cerrarFiltro" class="rounded-lg bg-gray-400 px-6 py-2 text-white transition hover:bg-gray-500">Cerrar</button>
                <button type="button" id="aplicarFiltro" class="rounded-lg bg-blue-600 px-6 py-2 text-white transition hover:bg-blue-700">Aplicar</button>
            </div>
        </div>
    </div>

    @php
        $historialJsCandidates = [
            public_path('legacy/js/historial_laravel.js'),
            public_path('legacy/assets/js/historial_laravel.js'),
        ];
        $historialJs = null;
        $historialJsAsset = 'legacy/assets/js/historial_laravel.js';
        foreach ($historialJsCandidates as $candidate) {
            if (is_file($candidate)) {
                $historialJs = $candidate;
                $historialJsAsset = str_contains(str_replace('\\', '/', $candidate), '/legacy/assets/')
                    ? 'legacy/assets/js/historial_laravel.js'
                    : 'legacy/js/historial_laravel.js';
                break;
            }
        }
    @endphp
    <script src="{{ asset($historialJsAsset) }}?v={{ $historialJs ? filemtime($historialJs) : 1 }}"></script>
    <script>
        window.setTimeout(function () {
            if (!window.EXACTO_HISTORIAL_READY) {
                var tb = document.getElementById('tabla');
                if (tb) {
                    tb.innerHTML = '<tr><td class="p-3 border text-center text-red-700" colspan="5">No se cargó historial_laravel.js. Sube public/legacy/assets/js/historial_laravel.js y recarga con Ctrl+F5.</td></tr>';
                }
            }
        }, 800);
    </script>
</body>
@endsection
