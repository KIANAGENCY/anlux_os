@extends('layouts.anlux_app')

@php
    $nav_admin_activo = 'integraciones';
@endphp

@push('styles')
    @vite(['resources/js/admin/integraciones/main.tsx'])
@endpush

@section('content')
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased">
    <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:py-8">
        @include('partials.nav-admin')
        <div id="admin-integraciones-react-root">
            <div class="anlux-page-card p-6">Cargando configuración de comunicaciones…</div>
        </div>
        <script type="application/json" id="react-page-props">
            {!! json_encode([
                'settings' => $settings,
                'csrf' => csrf_token(),
                'status' => session('status'),
                'error' => session('error'),
                'errors' => $errors->toArray(),
                'actions' => [
                    'update' => route('admin.integrations.update'),
                    'testMail' => route('admin.integrations.testMail'),
                    'testWhatsapp' => route('admin.integrations.testWhatsapp'),
                ],
                'webhookUrl' => route('webhooks.whatsapp.verify'),
            ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
        </script>
    </div>
</body>
@endsection
