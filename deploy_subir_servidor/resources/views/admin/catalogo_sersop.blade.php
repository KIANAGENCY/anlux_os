@extends('layouts.exacto_app')

@section('content')
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased">
    @include('partials.admin-page-open')
        @include('partials.nav-admin')
        <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-blue-700 sm:text-3xl">Cat&aacute;logo SERSOP</h1>
                <p class="mt-2 text-sm text-gray-600">Edita claves, descripciones y <strong>PRECIOS SIN IVA</strong> usados en la orden de servicio. El IVA (16%) se calcula solo en los totales de la orden.</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <button type="button" id="btnAgregarServicioSersop" class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-700 px-4 py-3 text-sm font-bold text-white shadow hover:bg-blue-800">
                    <i class="fas fa-plus"></i>
                    Agregar clave
                </button>
                <form action="{{ route('admin.catalogo.syncPreciosSinIva') }}" method="POST" class="inline" onsubmit="return confirm('Esto reemplaza el catálogo con los PRECIOS SIN IVA del archivo de configuración (ej. SERSOP01 = 603.45). ¿Continuar?');">
                    @csrf
                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border-2 border-emerald-600 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800 shadow hover:bg-emerald-100 sm:w-auto">
                        <i class="fas fa-sync-alt"></i>
                        Aplicar precios SIN IVA
                    </button>
                </form>
            </div>
        </div>
        @if(session('success'))
            <div class="mb-5 rounded-lg bg-green-500 p-4 text-white">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-5 rounded-lg bg-red-500 p-4 text-white">{{ session('error') }}</div>
        @endif
        <form action="{{ route('admin.catalogo.update') }}" method="POST" class="space-y-5">
            @csrf
            <div class="overflow-x-auto rounded-lg border border-blue-100">
                <table class="w-full min-w-[920px] border-collapse text-sm">
                    <thead>
                        <tr class="bg-blue-600 text-white">
                            <th class="border p-3 text-left">CLAVE</th>
                            <th class="border p-3 text-left">DESCRIPCION</th>
                            <th class="border p-3 text-left">PRECIO SIN IVA</th>
                            <th class="border p-3 text-center">EDITABLE EN ORDEN</th>
                            <th class="border p-3 text-center">ACTIVO</th>
                            <th class="border p-3 text-center">QUITAR</th>
                        </tr>
                    </thead>
                    <tbody id="catalogoSersopBody">
                        @foreach($catalogo as $i => $servicio)
                            <tr class="catalogo-sersop-row hover:bg-blue-50">
                                <td class="border p-3"><input type="text" name="servicios[{{ $i }}][clave]" value="{{ $servicio['clave'] }}" class="w-full rounded border border-blue-300 px-2 py-1 font-semibold uppercase text-blue-900"></td>
                                <td class="border p-3"><input type="text" name="servicios[{{ $i }}][descripcion]" value="{{ $servicio['descripcion'] }}" class="w-full rounded border border-blue-300 px-2 py-1"></td>
                                <td class="border p-3"><input type="number" step="0.01" min="0" name="servicios[{{ $i }}][precio]" value="{{ number_format((float)$servicio['precio'], 2, '.', '') }}" class="w-full rounded border border-blue-300 px-2 py-1"></td>
                                <td class="border p-3 text-center"><input type="checkbox" name="servicios[{{ $i }}][editable]" {{ $servicio['editable'] ? 'checked' : '' }} class="h-5 w-5"></td>
                                <td class="border p-3 text-center"><input type="checkbox" name="servicios[{{ $i }}][activo]" {{ $servicio['activo'] ? 'checked' : '' }} class="h-5 w-5"></td>
                                <td class="border p-3 text-center"><button type="button" class="btn-quitar-servicio-sersop font-bold text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if(!empty($canEditPdfCondiciones))
                <div class="rounded-lg border border-blue-200 bg-blue-50/80 p-4 sm:p-6">
                    <h2 class="mb-2 text-lg font-bold text-blue-900">Condiciones de entrega del equipo (PDF)</h2>
                    <p class="mb-3 text-sm text-slate-600">Texto del recuadro azul en el PDF de la orden. Una condición por línea (puede empezar con <code class="rounded bg-white px-1">*</code>).</p>
                    <label for="condiciones_pdf" class="sr-only">Condiciones del PDF</label>
                    <textarea id="condiciones_pdf" name="condiciones_pdf" rows="14" class="w-full rounded-lg border-2 border-blue-300 bg-white p-3 font-mono text-sm text-slate-900 shadow-inner focus:border-blue-600 focus:outline-none">{{ old('condiciones_pdf', $condicionesPdf ?? '') }}</textarea>
                </div>
            @endif
            <button type="submit" class="w-full rounded-lg bg-gradient-to-r from-blue-500 to-blue-700 px-4 py-3 font-bold text-white shadow hover:shadow-lg">{{ !empty($canEditPdfCondiciones) ? 'Guardar catálogo y condiciones del PDF' : 'Guardar catálogo' }}</button>
        </form>
    @php
        $adminCatalogoJsPath = public_path('legacy/assets/js/admin_catalogo_sersop.js');
        $adminCatalogoJsV = is_file($adminCatalogoJsPath) ? filemtime($adminCatalogoJsPath) : 1;
    @endphp
    <script src="{{ asset('legacy/assets/js/admin_catalogo_sersop.js') }}?v={{ $adminCatalogoJsV }}" defer></script>
    @include('partials.admin-page-close')
</body>
@endsection
