# Shared Layouts

## Anlux App Layout

- Path: `resources/views/layouts/anlux_app.blade.php`
- Purpose: Authenticated application shell. Loads the compiled Tailwind CSS, Font Awesome, CSRF/base URL globals, stacked page styles, and page content.

```blade
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle ?? 'Anlux' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('legacy/public/img/anlux-icon-1024.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('legacy/public/img/anlux-icon-1024.png') }}">
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
    <style>
        body.anlux-pdf-open nav[aria-label="Navegacion principal"],
        body.anlux-pdf-open nav[aria-label="Navegacion admin"],
        body.anlux-pdf-open nav[aria-label="Navegación principal"],
        body.anlux-pdf-open nav[aria-label="Navegación admin"] { display: none !important; }
    </style>
    @stack('styles')
    {!! $pageHeadExtra ?? '' !!}
</head>
@yield('content')
</html>
```

## Order Page Wrapper

- Path: `resources/views/orders/orden_page.blade.php`
- Purpose: Wraps the full order form in the authenticated shell.

```blade
@extends('layouts.anlux_app')

@section('content')
    @include('orders.orden_form')
@endsection
```

## Shared navigation

- `resources/views/partials/nav-app.blade.php`: authenticated top navigation.
- `resources/views/partials/nav-anlux-user-bar.blade.php`: current technician, account switching, and shared dialog scripts.
- `resources/views/partials/header-flujo-tres.blade.php`: three-card navigation between registered orders, new order, and PDF history.

