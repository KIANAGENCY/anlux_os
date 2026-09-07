@extends('layouts.anlux_app')

@section('content')
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased">
    @include('partials.admin-page-open')
        <div id="admin-registro-react-root"></div>
        <script type="application/json" id="react-page-props">
            {!! json_encode([
                'action' => route('admin.registro.store'),
                'csrf' => csrf_token(),
                'old' => [
                    'nombre' => old('nombre', ''),
                    'nombre_usuario' => old('nombre_usuario', ''),
                    'email' => old('email', ''),
                    'celular' => old('celular', ''),
                    'perfil' => old('perfil', ''),
                ],
                'success' => session('success'),
                'error' => session('error'),
            ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
        </script>
        @vite(['resources/js/admin/registro/main.tsx'])
    @include('partials.admin-page-close')
</body>
@endsection
