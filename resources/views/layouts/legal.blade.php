<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle ?? 'Legal' }} — Exacto</title>
    <link rel="icon" type="image/png" href="{{ asset('legacy/public/img/exacto-icon-1024.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('legacy/public/img/exacto-icon-1024.png') }}">
    @php
        $ogTitle = ($pageTitle ?? 'Legal') . ' — Exacto';
        $ogDescriptionText = $ogDescription ?? 'Exacto — Sistema interno de ordenes y administracion.';
        $ogImageRelative = is_file(public_path('legacy/public/img/og-exacto.jpg'))
            ? 'legacy/public/img/og-exacto.jpg'
            : 'legacy/public/img/logo.jpeg';
        $ogImage = url($ogImageRelative);
        $ogUrl = url()->current();
        $ogUsesStandardCanvas = str_ends_with($ogImageRelative, 'og-exacto.jpg');
        $fbAppId = trim((string) config('services.whatsapp.app_id', ''));
    @endphp
    <meta name="description" content="{{ $ogDescriptionText }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Exacto">
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:description" content="{{ $ogDescriptionText }}">
    <meta property="og:url" content="{{ $ogUrl }}">
    <meta property="og:locale" content="es_MX">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:secure_url" content="{{ $ogImage }}">
    <meta property="og:image:type" content="image/jpeg">
    <meta property="og:image:alt" content="Exacto — Expertos en administracion y computo">
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @php
        $legalCssPath = public_path('legacy/assets/css/legal.css');
        $legalCssV = is_file($legalCssPath) ? filemtime($legalCssPath) : 1;
    @endphp
    <link rel="stylesheet" href="{{ asset('legacy/assets/css/legal.css') }}?v={{ $legalCssV }}">
</head>
<body>
    <div class="legal-shell">
        <div class="legal-top">
            <a href="{{ route('home') }}" class="legal-back">
                <i class="fas fa-arrow-left"></i> Volver al inicio
            </a>
        </div>

        <article class="legal-card">
            @yield('content')
        </article>

        <footer class="legal-footer">
            <span>Exacto | Sistema interno de ordenes y administracion</span>
            <nav aria-label="Enlaces legales">
                <a href="{{ route('legal.terminos') }}">Terminos y condiciones</a>
                <span class="sep" aria-hidden="true">|</span>
                <a href="{{ route('legal.privacidad') }}">Aviso de privacidad</a>
                <span class="sep" aria-hidden="true">|</span>
                <a href="{{ route('legal.eliminar-datos') }}">Eliminacion de datos</a>
            </nav>
        </footer>
    </div>
</body>
</html>
