@php
    $nav_activo = $nav_activo ?? '';
    $claseActivo = 'inline-flex items-center justify-center gap-2 rounded-lg bg-blue-700 px-4 py-2 text-sm font-bold text-white shadow';
    $claseInactivo = 'inline-flex items-center justify-center gap-2 rounded-lg border-2 border-blue-300 bg-white px-4 py-2 text-sm font-bold text-blue-800 transition hover:bg-blue-50';
    $rawNombreTecnico = html_entity_decode((string) ($nombreTecnico ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $nombreTecnicoMostrado = trim($rawNombreTecnico);
    if ($nombreTecnicoMostrado !== '' && str_starts_with($nombreTecnicoMostrado, 'v1:')) {
        $revealed = app(\App\Services\AnluxVaultService::class)->revealString($nombreTecnicoMostrado, false);
        if ($revealed !== '') {
            $nombreTecnicoMostrado = $revealed;
        } elseif (auth()->check()) {
            $fallback = trim((string) (auth()->user()?->nombre_tecnico ?? ''));
            if ($fallback !== '') {
                $nombreTecnicoMostrado = $fallback;
            }
        }
    }
    if ($nombreTecnicoMostrado !== '' && str_starts_with($nombreTecnicoMostrado, 'v1:')) {
        $idTecnico = (int) (auth()->user()?->id_tecnico ?? 0);
        $nombreTecnicoMostrado = $idTecnico > 0 ? ('Técnico #'.$idTecnico) : 'Técnico';
    }
@endphp
@include('partials.anlux-cutover-notice')

<nav
    class="sticky top-0 z-50 mb-6 flex flex-col gap-3 rounded-lg border border-blue-200 bg-white/95 px-4 py-3 shadow-sm backdrop-blur-[2px] sm:min-h-[76px] lg:flex-row lg:items-center lg:justify-between"
    aria-label="Navegación principal"
    style="position: sticky; top: 0; z-index: 9999; visibility: visible; opacity: 1; backdrop-filter: blur(2px);"
>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:flex-wrap">
        <a href="{{ route('ordenes.index') }}" class="inline-flex shrink-0 items-center gap-2" aria-label="Ir a órdenes registradas">
            <img src="{{ asset('legacy/public/img/logo.jpeg') }}?v={{ @filemtime(public_path('legacy/public/img/logo.jpeg')) ?: 1 }}" alt="Logo Anlux" class="h-12 w-auto object-contain sm:h-14" style="max-width: 230px;">
        </a>
    </div>
    <div class="flex h-full flex-col justify-center gap-2 self-stretch sm:flex-row sm:items-center sm:justify-end sm:gap-4">
        @include('partials.nav-anlux-user-bar')
        <button type="button" id="navBtnCerrarSesion" class="inline-flex items-center justify-center gap-2 rounded-lg border-2 border-red-300 bg-white px-4 py-2 text-sm font-bold text-red-700 shadow-sm transition hover:bg-red-50">
            <i class="fas fa-sign-out-alt"></i>
            Cerrar sesión
        </button>
    </div>
</nav>

@php
    $navAppCssPath = public_path('legacy/assets/css/nav_app.css');
    $navAppCssV = is_file($navAppCssPath) ? filemtime($navAppCssPath) : 1;
@endphp
@push('styles')
<link rel="stylesheet" href="{{ asset('legacy/assets/css/nav_app.css') }}?v={{ $navAppCssV }}">
@endpush

<div id="modalCerrarSesion" class="hidden fixed inset-0 z-[100] items-center justify-center bg-blue-950/40 p-4 backdrop-blur-[2px]" role="dialog" aria-modal="true" aria-labelledby="modalCerrarSesionTitulo">
    <div class="nav-modal-panel w-full max-w-md overflow-hidden rounded-xl border-2 border-blue-200 bg-white shadow-2xl shadow-blue-900/15">
        <div class="bg-gradient-to-r from-blue-700 to-blue-600 px-6 py-4 text-white">
            <h3 id="modalCerrarSesionTitulo" class="text-lg font-bold tracking-tight">
                <i class="fas fa-sign-out-alt mr-2 opacity-90"></i>¿Cerrar sesión?
            </h3>
        </div>
        <div class="border-t border-blue-100 bg-white px-6 py-5">
            <p class="mb-6 text-sm leading-relaxed text-blue-900/85">Vas a salir de tu cuenta en este navegador. Podrás volver a entrar cuando quieras.</p>
            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" id="modalCerrarSesionCancelar" class="rounded-lg border-2 border-blue-300 bg-white px-5 py-2.5 text-sm font-semibold text-blue-800 shadow-sm transition hover:bg-blue-50">
                    Cancelar
                </button>
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg border-2 border-red-500 bg-white px-5 py-2.5 text-sm font-bold text-red-700 shadow-sm transition hover:bg-red-50 sm:w-auto">
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('legacy/assets/js/nav_app.js') }}"></script>
