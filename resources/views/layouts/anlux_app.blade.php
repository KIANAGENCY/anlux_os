<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle ?? 'Anlux' }}</title>
    <link rel="stylesheet" href="{{ asset('legacy/assets/css/app.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @php
        $anluxWhatsappUiEnabled = config('anlux.whatsapp_notifications_enabled', false)
            && filter_var(config('services.whatsapp.enabled', false), FILTER_VALIDATE_BOOL);
    @endphp
    <script>
        window.ANLUX_CSRF_TOKEN = @json(csrf_token());
        window.ANLUX_BASE_URL = @json(url(''));
        window.ANLUX_WHATSAPP_ENABLED = @json($anluxWhatsappUiEnabled);
    </script>
    @vite(['resources/css/app.css'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@400;500;600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
    @include('partials.anlux-brand-head')
    <script>
        (function () {
            var base = String(window.ANLUX_BASE_URL || '').replace(/\/$/, '');
            function loggedOutFlag() {
                try {
                    if (localStorage.getItem('anlux_logged_out')) return true;
                } catch (e) {}
                try {
                    if (document.cookie.split(';').some(function (c) {
                        return c.trim().indexOf('anlux_client_logged_out=1') === 0;
                    })) return true;
                } catch (e) {}
                return false;
            }
            function kickToLogin() {
                try { window.stop(); } catch (e) {}
                try {
                    document.documentElement.innerHTML = '<body style="background:#0f172a;color:#fff;font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0">Saliendo…</body>';
                } catch (e) {}
                try { window.location.replace(base + '/login?logged_out=1'); } catch (e) { window.location.href = base + '/login?logged_out=1'; }
            }
            // Respuesta autenticada del servidor: confiar en la sesión y quitar flags viejos del cliente.
            try { localStorage.removeItem('anlux_logged_out'); } catch (e) {}
            try { sessionStorage.removeItem('anlux_logged_out'); } catch (e) {}
            try {
                var secure = window.location.protocol === 'https:' ? '; Secure' : '';
                document.cookie = 'anlux_client_logged_out=; Path=/; Max-Age=0; SameSite=Lax' + secure;
            } catch (e) {}
            window.addEventListener('pageshow', function (event) {
                // Tras logout, no mostrar HTML autenticado restaurado (atrás / bfcache).
                if (event.persisted && loggedOutFlag()) {
                    kickToLogin();
                } else if (event.persisted) {
                    window.location.reload();
                }
            });
            window.addEventListener('popstate', function () {
                if (loggedOutFlag()) kickToLogin();
            });
        })();
    </script>
    <style>
        body.anlux-pdf-open nav[aria-label="Navegacion principal"],
        body.anlux-pdf-open nav[aria-label="Navegacion admin"],
        body.anlux-pdf-open nav[aria-label="Navegación principal"],
        body.anlux-pdf-open nav[aria-label="Navegación admin"] {
            display: none !important;
        }
    </style>
    @stack('styles')
    {!! $pageHeadExtra ?? '' !!}
</head>
@yield('content')
@php
    $anluxUiJsPath = public_path('legacy/assets/js/anlux_ui.js');
    $anluxUiJsVer = is_file($anluxUiJsPath) ? filemtime($anluxUiJsPath) : 1;
@endphp
@include('partials.anlux-ui-modal')
<script src="{{ asset('legacy/assets/js/anlux_ui.js') }}?v={{ $anluxUiJsVer }}"></script>
</html>
