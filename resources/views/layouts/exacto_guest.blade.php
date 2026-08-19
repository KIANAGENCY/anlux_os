<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle ?? __('Iniciar sesión') }} — {{ config('app.name', 'Exacto') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('legacy/public/img/exacto-icon-1024.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('legacy/public/img/exacto-icon-1024.png') }}">
    <link rel="stylesheet" href="{{ asset('legacy/assets/css/app.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        window.EXACTO_CSRF_TOKEN = @json(csrf_token());
    </script>
</head>
<body class="flex min-h-screen items-center justify-center overflow-y-auto bg-gradient-to-br from-blue-600 via-blue-400 to-cyan-400 px-4 py-6">
<div class="z-10 w-full max-w-md">
    @include('partials.exacto-cutover-notice', ['variant' => 'guest'])
    @yield('content')
</div>
</body>
</html>
