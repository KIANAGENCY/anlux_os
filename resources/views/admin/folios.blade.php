@extends('layouts.anlux_app')

@php
    $pageTitle = $pageTitle ?? 'Folios de órdenes - Anlux';
    $status = $status ?? ['anio' => (int) date('Y'), 'next_num' => 1, 'max_usado' => 0, 'proximo_folio' => '', 'huecos' => []];
    $anio = (int) ($anio ?? date('Y'));
@endphp

@section('content')
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased">
    @include('partials.admin-page-open')
        <div id="admin-folios-react-root"></div>
        <script type="application/json" id="react-page-props">
            {!! json_encode([
                'anio' => $anio,
                'indexAction' => route('admin.folios.index'),
                'syncAction' => route('admin.folios.sync'),
                'success' => session('success'),
                'status' => [
                    'next_num' => (int) ($status['next_num'] ?? 1),
                    'max_usado' => (int) ($status['max_usado'] ?? 0),
                    'proximo_folio' => (string) ($status['proximo_folio'] ?? ''),
                    'huecos' => array_values($status['huecos'] ?? []),
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
        </script>
        @vite(['resources/js/admin/folios/main.tsx'])
    @include('partials.admin-page-close')
</body>
@endsection
