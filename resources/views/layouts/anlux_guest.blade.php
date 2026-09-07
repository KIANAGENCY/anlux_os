<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle ?? __('Iniciar sesión') }} — {{ config('app.name', 'Anlux') }}</title>
    <link rel="stylesheet" href="{{ asset('legacy/assets/css/app.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/app.css'])
    @include('partials.anlux-brand-head')
    <script>
        window.ANLUX_CSRF_TOKEN = @json(csrf_token());
        window.ANLUX_BASE_URL = @json(url(''));
    </script>
    @stack('styles')
</head>
<body class="flex min-h-screen items-center justify-center overflow-y-auto bg-gradient-to-br from-blue-600 via-blue-400 to-cyan-400 px-4 py-6">
<div class="z-10 w-full max-w-md">
    @include('partials.anlux-cutover-notice', ['variant' => 'guest'])
    @yield('content')
</div>
</body>
</html>
