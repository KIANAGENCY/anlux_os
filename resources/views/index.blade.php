<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle ?? 'Inicio - Anlux' }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @php
        $homeCssPath = public_path('legacy/assets/css/home.css');
        $homeCssV = is_file($homeCssPath) ? filemtime($homeCssPath) : 1;
        $logoUrl = $anluxLogoUrl ?? asset('legacy/public/img/logo.jpeg');
        $nombreSesion = session('nombre_tecnico') ?: ($user?->nombre_tecnico ?? '');
    @endphp
    <link rel="stylesheet" href="{{ asset('legacy/assets/css/home.css') }}?v={{ $homeCssV }}">
    @vite(['resources/css/app.css'])
    @include('partials.anlux-brand-head')
    <script>
        window.ANLUX_CSRF_TOKEN = @json(csrf_token());
        window.ANLUX_BASE_URL = @json(url(''));
    </script>
</head>
<body>
    <div id="home-react-root"></div>
    <script type="application/json" id="react-page-props">
        {!! json_encode([
            'authenticated' => (bool) $user,
            'isAdmin' => (bool) ($isAdmin ?? false),
            'nombreSesion' => (string) $nombreSesion,
            'logoUrl' => $logoUrl,
            'urls' => [
                'login' => route('login'),
                'ordenes' => route('ordenes.index'),
                'nuevaOrden' => route('orden_servicio.create'),
                'historial' => route('historial.index'),
                'profile' => route('profile.edit'),
                'admin' => route('admin.index'),
                'usuarios' => route('admin.users.index'),
                'seguridad' => route('admin.seguridad.index'),
                'terminos' => route('legal.terminos'),
                'privacidad' => route('legal.privacidad'),
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
    </script>
    @vite(['resources/js/home/main.tsx'])
</body>
</html>
