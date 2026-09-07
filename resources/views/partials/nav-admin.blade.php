@php
    $nav_admin_activo = $nav_admin_activo ?? '';
    $logoUrl = $anluxLogoUrl ?? asset('legacy/public/img/logo.jpeg');
@endphp

@include('partials.anlux-cutover-notice')

<div id="anlux-nav-admin-root"></div>
<script type="application/json" id="anlux-nav-props">
{!! json_encode([
    'variant' => 'admin',
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
    'navAdminActivo' => $nav_admin_activo,
    'adminLinks' => [
        ['key' => 'admin', 'href' => route('admin.index'), 'icon' => 'fa-home', 'label' => 'Panel'],
        ['key' => 'catalogo_sersop', 'href' => route('admin.catalogo.index'), 'icon' => 'fa-list', 'label' => 'Catálogo'],
        ['key' => 'registro', 'href' => route('admin.registro.create'), 'icon' => 'fa-user-plus', 'label' => 'Registro'],
        ['key' => 'usuarios', 'href' => route('admin.users.index'), 'icon' => 'fa-users', 'label' => 'Usuarios'],
        ['key' => 'integraciones', 'href' => route('admin.integrations.index'), 'icon' => 'fa-plug', 'label' => 'Integraciones'],
        ['key' => 'apariencia', 'href' => route('admin.appearance.index'), 'icon' => 'fa-palette', 'label' => 'Apariencia'],
        ['key' => 'seguridad_actividad', 'href' => route('admin.seguridad.index'), 'icon' => 'fa-shield-alt', 'label' => 'Seguridad'],
        ['key' => 'folios', 'href' => route('admin.folios.index'), 'icon' => 'fa-hashtag', 'label' => 'Folios'],
    ],
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
</script>
@php
    $anluxUserBarCssPath = public_path('legacy/assets/css/anlux_user_bar.css');
    $anluxUserBarCssV = is_file($anluxUserBarCssPath) ? filemtime($anluxUserBarCssPath) : 1;
@endphp
<link rel="stylesheet" href="{{ asset('legacy/assets/css/anlux_user_bar.css') }}?v={{ $anluxUserBarCssV }}">
@vite(['resources/js/shared/nav/main.tsx'])
