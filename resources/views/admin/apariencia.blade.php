@extends('layouts.anlux_app')

@php
    $nav_admin_activo = 'apariencia';
@endphp

@push('styles')
    @vite(['resources/js/admin/apariencia/main.tsx'])
@endpush

@section('content')
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased">
    <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:py-8">
        @include('partials.nav-admin')
        <div id="admin-apariencia-react-root">
            <div class="anlux-page-card p-6">Cargando editor de apariencia…</div>
        </div>
        <script type="application/json" id="react-page-props">
            {!! json_encode([
                'appearance' => $appearance,
                'fonts' => $fonts,
                'logoUrl' => $logoUrl,
                'csrf' => csrf_token(),
                'status' => session('status'),
                'errors' => $errors->toArray(),
                'actions' => [
                    'update' => route('admin.appearance.update'),
                    'reset' => route('admin.appearance.reset'),
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
        </script>
    </div>
</body>
@endsection
