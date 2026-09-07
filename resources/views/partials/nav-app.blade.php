@php
    $nav_activo = $nav_activo ?? '';
    $logoUrl = $anluxLogoUrl ?? asset('legacy/public/img/logo.jpeg');
    $navAppCssPath = public_path('legacy/assets/css/nav_app.css');
    $navAppCssV = is_file($navAppCssPath) ? filemtime($navAppCssPath) : 1;
@endphp
@include('partials.anlux-cutover-notice')

<link rel="stylesheet" href="{{ asset('legacy/assets/css/nav_app.css') }}?v={{ $navAppCssV }}">

<div id="anlux-nav-app-root"></div>
<script type="application/json" id="anlux-nav-props">
{!! json_encode([
    'variant' => 'app',
    'csrf' => csrf_token(),
    'logoUrl' => $logoUrl,
    'logoutUrl' => route('logout'),
    'ordenesUrl' => route('ordenes.index'),
    'nombreTecnico' => $nombreTecnicoMostrado ?? '',
    'isImpersonating' => (bool) ($anluxIsImpersonating ?? false),
    'canSwitchAccount' => (bool) ($anluxCanSwitchAccount ?? false),
    'userId' => (int) ($anluxUserId ?? 0),
    'accounts' => $anluxSwitchAccounts ?? [],
    'isAdmin' => (bool) ($anluxIsAdmin ?? false),
    'isTechnician' => (bool) ($anluxIsTechnician ?? false),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
</script>
@vite(['resources/js/shared/nav/main.tsx'])
