<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle ?? 'Exacto' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('legacy/public/img/exacto-icon-1024.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('legacy/public/img/exacto-icon-1024.png') }}">
    <link rel="stylesheet" href="{{ asset('legacy/assets/css/app.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @php
        $exactoWhatsappUiEnabled = config('exacto.whatsapp_notifications_enabled', false)
            && filter_var(config('services.whatsapp.enabled', false), FILTER_VALIDATE_BOOL);
    @endphp
    <script>
        window.EXACTO_CSRF_TOKEN = @json(csrf_token());
        window.EXACTO_BASE_URL = @json(url(''));
        window.EXACTO_WHATSAPP_ENABLED = @json($exactoWhatsappUiEnabled);
    </script>
    <style>
        body.exacto-pdf-open nav[aria-label="Navegacion principal"],
        body.exacto-pdf-open nav[aria-label="Navegacion admin"],
        body.exacto-pdf-open nav[aria-label="Navegación principal"],
        body.exacto-pdf-open nav[aria-label="Navegación admin"] {
            display: none !important;
        }
    </style>
    @stack('styles')
    {!! $pageHeadExtra ?? '' !!}
</head>
@yield('content')
</html>
