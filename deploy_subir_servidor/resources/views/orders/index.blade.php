@extends('layouts.exacto_app')

{{-- Debe ir fuera de @section: si va dentro del yield, el @stack del <head> ya pasó y ordenes.css no carga. --}}
@php
    $ordenesCssPath = public_path('legacy/assets/css/ordenes.css');
    $ordenesCssV = is_file($ordenesCssPath) ? filemtime($ordenesCssPath) : 1;
@endphp
@push('styles')
    <link rel="stylesheet" href="{{ asset('legacy/assets/css/ordenes.css') }}?v={{ $ordenesCssV }}">
@endpush

@section('content')
<body class="bg-blue-50 px-2 py-3 sm:px-3 sm:py-4 lg:px-4 lg:py-6">
    <div class="mx-auto w-full max-w-[1800px] rounded-lg bg-white p-3 shadow-lg sm:p-5 lg:p-6">
        @include('partials.nav-app')

        @if(session('error'))
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                {{ session('error') }}
            </div>
        @endif

        @include('partials.header-flujo-tres')

        <section class="mb-2 mt-2 rounded-r-lg border-l-4 border-blue-700 bg-blue-50 p-3 sm:p-4 sm:pl-5">
            <h2 class="mb-4 flex items-center text-xl font-bold text-blue-900 sm:text-2xl">
                <i class="mr-3 text-blue-700 fas fa-search"></i>B&Uacute;SQUEDA Y FILTROS
            </h2>
            <div class="flex flex-col gap-3 md:flex-row">
                <input id="search" placeholder="Buscar cliente o folio..." class="flex-1 px-4 py-2 border-2 border-blue-300 rounded-lg focus:outline-none focus:border-blue-700 focus:bg-blue-50">
                <button id="buscar" class="rounded-lg bg-blue-600 px-6 py-2 text-white transition hover:bg-blue-700 md:w-auto">
                    <i class="fas fa-search"></i>
                </button>
                <button id="filtroBtn" class="rounded-lg bg-blue-600 px-6 py-2 text-white transition hover:bg-blue-700 md:w-auto">
                    <i class="fas fa-filter"></i>
                </button>
            </div>
        </section>

        <section class="mt-2 rounded-r-lg border-l-4 border-blue-600 bg-blue-50 p-3 sm:p-4 sm:pl-5">
            <h2 class="mb-4 flex items-center text-xl font-bold text-blue-900 sm:text-2xl">
                <i class="mr-3 text-blue-600 fas fa-list"></i>&Oacute;RDENES DE SERVICIO
            </h2>
            <div class="w-full overflow-x-auto rounded-lg border border-blue-100 bg-white">
                <table class="w-full border-collapse text-sm" style="width:100%; min-width:100%; table-layout:auto;">
                    <thead>
                        <tr class="text-white bg-blue-600">
                            <th class="p-3 text-left border whitespace-nowrap">NO. ORDEN</th>
                            <th class="p-3 text-left border min-w-[10rem]">CLIENTE</th>
                            <th class="p-3 text-left border whitespace-nowrap">ENTRADA</th>
                            <th class="p-3 text-left border whitespace-nowrap">TERMINADA</th>
                            <th class="p-3 text-left border whitespace-nowrap">ENTREGA</th>
                            <th class="p-3 text-center border min-w-[8rem]" title="Aviso si el equipo salió temporalmente del taller (la orden sigue En proceso)">SALIDA TEMP.</th>
                            <th class="p-3 text-left border min-w-[12rem]" title="Quien asign&oacute; o abri&oacute; la orden al pasar a taller (en proceso)">TECNICO</th>
                            <th class="p-3 text-left border min-w-[16rem]" title="T&eacute;cnicos anteriores y actuales que entraron a la orden (1., 2., ...)">INVOLUCRADOS</th>
                            <th class="p-3 text-center border min-w-[9rem]">
                                <div class="flex flex-wrap items-center justify-center gap-2">
                                    <span>ESTATUS</span>
                                    <button type="button" id="btnSortEstatus" class="inline-flex items-center justify-center rounded-md border border-white/40 bg-white/10 px-2 py-1 text-xs font-semibold text-white hover:bg-white/20" title="Ordenar por flujo de estatus (Recepci&oacute;n &rarr; Entregado) o por fecha de entrada">
                                        <i class="fas fa-sort" id="iconSortEstatusFecha"></i>
                                        <i class="fas fa-sort-amount-down hidden" id="iconSortEstatusFlujo"></i>
                                    </button>
                                </div>
                            </th>
                            <th class="p-3 text-center border whitespace-nowrap">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody id="tabla"></tbody>
                </table>
            </div>
            <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p id="paginationInfo" class="text-sm text-slate-600">Cargando &oacute;rdenes...</p>
                <div class="flex flex-wrap items-center gap-2">
                    <label for="perPage" class="text-sm text-slate-600">Mostrar</label>
                    <select id="perPage" class="px-3 py-2 border border-blue-200 rounded-lg text-sm">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                    <button type="button" id="prevPage" class="px-3 py-2 rounded-lg bg-blue-100 text-blue-700 hover:bg-blue-200 disabled:opacity-50 disabled:cursor-not-allowed">Anterior</button>
                    <span id="pageNumber" class="text-sm font-semibold text-blue-900">1</span>
                    <button type="button" id="nextPage" class="px-3 py-2 rounded-lg bg-blue-100 text-blue-700 hover:bg-blue-200 disabled:opacity-50 disabled:cursor-not-allowed">Siguiente</button>
                </div>
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
                <label class="block mb-2"><input type="radio" name="filtro" value="rojo"> &#128308; Recepci&oacute;n</label>
                <label class="block mb-2"><input type="radio" name="filtro" value="naranja"> &#128992; En proceso</label>
                <label class="block mb-2"><input type="radio" name="filtro" value="amarillo"> &#128993; Terminado</label>
                <label class="block"><input type="radio" name="filtro" value="verde"> &#128994; Entregado</label>
            </div>
            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button id="cerrarFiltro" class="rounded-lg bg-gray-400 px-6 py-2 text-white transition hover:bg-gray-500">Cerrar</button>
                <button id="aplicarFiltro" class="rounded-lg bg-blue-600 px-6 py-2 text-white transition hover:bg-blue-700">Aplicar</button>
            </div>
        </div>
    </div>

    <div id="modalEdit" class="hidden fixed inset-0 bg-black bg-opacity-50 justify-center items-center z-50 p-3">
        <div class="max-h-[90vh] w-full overflow-y-auto rounded-lg bg-white p-5 shadow-2xl sm:p-8" style="max-width: 420px; width: min(420px, 92vw);">
            <h3 class="mb-6 flex items-center justify-center text-center text-xl font-bold text-blue-700 sm:text-2xl">
                <i class="mr-3 fas fa-edit"></i>Editar Estatus
            </h3>
            <input type="hidden" id="editId">
            <input type="hidden" id="editFolio" value="">
            <input type="hidden" id="editFirmasRecepcionOk" value="0">
            <input type="hidden" id="editEstatusOrigen" value="">
            <div class="mb-6 p-4 bg-blue-50 rounded-lg">
                <div class="flex flex-col gap-3">
                    <label class="flex items-center gap-2"><input type="radio" name="editEstatus" value="rojo"> &#128308; Recepci&oacute;n</label>
                    <label class="flex items-center gap-2"><input type="radio" name="editEstatus" value="naranja"> &#128992; En proceso</label>
                    <label class="flex items-center gap-2"><input type="radio" name="editEstatus" value="amarillo"> &#128993; Terminado</label>
                </div>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-center">
                <div class="grid w-full grid-cols-1 gap-2 sm:flex sm:w-auto sm:flex-wrap">
                    <button type="button" onclick="verPdfInlineDesdeEdit()" class="flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
                        <i class="fas fa-file-pdf"></i>Ver PDF
                    </button>
                    <button type="button" onclick="descargarOrdenActual()" class="flex items-center justify-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-green-700">
                        <i class="fas fa-download"></i>Descargar
                    </button>
                </div>
                <div class="grid w-full grid-cols-1 gap-3 sm:w-auto sm:grid-cols-2">
                    <button id="cancelarEdit" class="rounded-lg bg-gray-400 px-4 py-2 text-white transition hover:bg-gray-500">Cancelar</button>
                    <button id="guardarEdit" class="rounded-lg bg-blue-600 px-4 py-2 text-white transition hover:bg-blue-700">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <div id="ordenesUiModal" class="hidden fixed inset-0 z-[85] items-center justify-center bg-slate-950/70 p-4" role="dialog" aria-modal="true" aria-labelledby="ordenesUiModalTitle">
        <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 id="ordenesUiModalTitle" class="text-center text-xl font-bold text-blue-900">Aviso</h3>
            </div>
            <div class="px-5 py-5">
                <p id="ordenesUiModalMessage" class="whitespace-pre-line text-center text-sm leading-6 text-slate-700"></p>
            </div>
            <div class="flex justify-center border-t border-slate-200 px-5 py-4">
                <button type="button" id="ordenesUiModalConfirm" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-blue-700">
                    Aceptar
                </button>
            </div>
        </div>
    </div>

    <div id="modalPdf" class="hidden fixed inset-0 z-[60] flex-col bg-slate-900/80 p-2 sm:p-4" role="dialog" aria-modal="true" aria-labelledby="modalPdfTituloOrdenes">
        <div class="flex shrink-0 flex-wrap items-center justify-between gap-2 rounded-t-lg bg-blue-800 px-4 py-3 text-white">
            <h3 id="modalPdfTituloOrdenes" class="text-lg font-bold truncate pr-2">
                <i class="fas fa-file-pdf mr-2 opacity-90"></i><span id="modalPdfFolioOrdenes">PDF</span>
            </h3>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" id="modalPdfNuevaPestanaOrdenes" class="rounded-lg border border-white/40 bg-white/10 px-3 py-2 text-sm font-semibold hover:bg-white/20">
                    <i class="fas fa-external-link-alt mr-1"></i>Abrir en pesta&ntilde;a
                </button>
                <button type="button" id="modalPdfCerrarOrdenes" class="rounded-lg bg-white px-4 py-2 text-sm font-bold text-blue-900 hover:bg-blue-50">
                    Cerrar
                </button>
            </div>
        </div>
        <div class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-b-lg bg-slate-800">
            <iframe id="iframePdfOrdenes" class="h-full min-h-[70vh] w-full flex-1 border-0 bg-white" title="Vista previa PDF de la orden"></iframe>
        </div>
    </div>

    @php
        $ordenesJs = public_path('legacy/js/ordenes_laravel.js');
        $pdfViewJsOrdenes = public_path('legacy/js/exacto_pdf_view.js');
    @endphp
    <script src="{{ asset('legacy/js/exacto_pdf_view.js') }}?v={{ is_file($pdfViewJsOrdenes) ? filemtime($pdfViewJsOrdenes) : 1 }}"></script>
    <script src="{{ asset('legacy/js/ordenes_laravel.js') }}?v={{ is_file($ordenesJs) ? filemtime($ordenesJs) : 1 }}"></script>
</body>
@endsection
