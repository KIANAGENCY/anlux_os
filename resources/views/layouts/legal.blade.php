<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle ?? 'Legal' }} — Anlux</title>
    @php
        $ogTitle = ($pageTitle ?? 'Legal') . ' — Anlux';
        $ogDescriptionText = $ogDescription ?? 'Anlux — Sistema interno de ordenes y administracion.';
        $ogImageRelative = is_file(public_path('legacy/public/img/og-anlux.jpg')) ? 'legacy/public/img/og-anlux.jpg' : 'legacy/public/img/logo.jpeg';
        $hasCustomBrandLogo = !empty($anluxBranding['logo_path'] ?? '');
        $ogImage = $hasCustomBrandLogo ? ($anluxLogoUrl ?? url($ogImageRelative)) : url($ogImageRelative);
        $ogUrl = url()->current();
        $ogUsesStandardCanvas = !$hasCustomBrandLogo && str_ends_with($ogImageRelative, 'og-anlux.jpg');
        $fbAppId = trim((string) config('services.whatsapp.app_id', ''));
        $legalCssPath = public_path('legacy/assets/css/legal.css');
        $legalCssV = is_file($legalCssPath) ? filemtime($legalCssPath) : 1;
    @endphp
    <meta name="description" content="{{ $ogDescriptionText }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Anlux">
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:description" content="{{ $ogDescriptionText }}">
    <meta property="og:url" content="{{ $ogUrl }}">
    <meta property="og:locale" content="es_MX">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:secure_url" content="{{ $ogImage }}">
    <meta property="og:image:type" content="image/jpeg">
    <meta property="og:image:alt" content="{{ config('app.name', 'Anlux') }} — Sistema de soporte tecnico">
    @if ($ogUsesStandardCanvas)
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
    @endif
    @if ($fbAppId !== '')
        <meta property="fb:app_id" content="{{ $fbAppId }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $ogTitle }}">
    <meta name="twitter:description" content="{{ $ogDescriptionText }}">
    <meta name="twitter:image" content="{{ $ogImage }}">
    <link rel="stylesheet" href="{{ asset('legacy/assets/css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('legacy/assets/css/legal.css') }}?v={{ $legalCssV }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/app.css'])
    @include('partials.anlux-brand-head')
    <script>
        window.ANLUX_CSRF_TOKEN = @json(csrf_token());
        window.ANLUX_BASE_URL = @json(url(''));
    </script>
</head>
<body>
    @yield('content')
</body>
</html>
