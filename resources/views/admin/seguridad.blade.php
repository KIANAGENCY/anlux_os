@extends('layouts.anlux_app')

@section('content')
@php
    $adminPageAncho = 'max-w-6xl';
@endphp
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased">
    @include('partials.admin-page-open')
        <div id="admin-seguridad-react-root"></div>
        <script type="application/json" id="react-page-props">
            {!! json_encode([
                'filterAction' => route('admin.seguridad.index'),
                'clearUrl' => route('admin.seguridad.index'),
                'secretActivo' => (bool) ($secretActivo ?? false),
                'totalOrdenes' => (int) ($totalOrdenes ?? 0),
                'actividad24h' => (int) ($actividad24h ?? 0),
                'alertas24h' => (int) ($alertas24h ?? 0),
                'bloqueos24h' => (int) ($bloqueos24h ?? 0),
                'severity' => (string) ($severity ?? ''),
                'eventType' => (string) ($eventType ?? ''),
                'ip' => (string) ($ip ?? ''),
                'eventTypes' => collect($eventTypes ?? [])->map(fn ($ev) => (string) ($ev->event_type ?? ''))->filter()->values()->all(),
                'recent' => collect($recent ?? [])->map(fn ($ev) => (array) $ev)->values()->all(),
                'suspiciousIps' => collect($suspiciousIps ?? [])->map(fn ($row) => (array) $row)->values()->all(),
            ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
        </script>
        @vite(['resources/js/admin/seguridad/main.tsx'])
    @include('partials.admin-page-close')
</body>
@endsection
