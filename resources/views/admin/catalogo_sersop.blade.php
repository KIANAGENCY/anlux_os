@extends('layouts.anlux_app')

@section('content')
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased">
    @include('partials.admin-page-open')
        <div id="admin-catalogo-react-root"></div>
        <script type="application/json" id="react-page-props">
            {!! json_encode([
                'updateAction' => route('admin.catalogo.update'),
                'syncAction' => route('admin.catalogo.syncPreciosSinIva'),
                'csrf' => csrf_token(),
                'catalogo' => collect($catalogo ?? [])->map(fn ($s) => [
                    'clave' => (string) ($s['clave'] ?? ''),
                    'descripcion' => (string) ($s['descripcion'] ?? ''),
                    'precio' => (float) ($s['precio'] ?? 0),
                    'editable' => (bool) ($s['editable'] ?? false),
                    'activo' => (bool) ($s['activo'] ?? true),
                ])->values()->all(),
                'condicionesPdf' => old('condiciones_pdf', $condicionesPdf ?? ''),
                'canEditPdfCondiciones' => !empty($canEditPdfCondiciones),
                'success' => session('success'),
                'error' => session('error'),
            ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
        </script>
        @vite(['resources/js/admin/catalogo/main.tsx'])
    @include('partials.admin-page-close')
</body>
@endsection
