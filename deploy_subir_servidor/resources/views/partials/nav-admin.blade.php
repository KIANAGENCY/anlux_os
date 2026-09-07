@php
    $nav_admin_activo = $nav_admin_activo ?? '';
    $navLink = static function (string $key, string $href, string $icon, string $label) use ($nav_admin_activo): string {
        $isActive = $nav_admin_activo === $key;
        $base = 'inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold transition';
        $classes = $isActive
            ? $base.' bg-blue-700 text-white shadow-sm'
            : $base.' text-slate-600 hover:bg-slate-100 hover:text-blue-800';

        return '<a href="'.e($href).'" class="'.$classes.'"><i class="fas '.e($icon).'" aria-hidden="true"></i><span>'.$label.'</span></a>';
    };
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
    class="rounded-xl border border-slate-200 bg-slate-50 p-3 sm:p-4"
    aria-label="Navegaci&oacute;n admin"
>
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex flex-wrap gap-1.5 sm:gap-2">
            {!! $navLink('admin', route('admin.index'), 'fa-home', 'Panel') !!}
            {!! $navLink('catalogo_sersop', route('admin.catalogo.index'), 'fa-list', 'Cat&aacute;logo') !!}
            {!! $navLink('registro', route('admin.registro.create'), 'fa-user-plus', 'Registro') !!}
            {!! $navLink('usuarios', route('admin.users.index'), 'fa-users', 'Usuarios') !!}
            {!! $navLink('seguridad_actividad', route('admin.seguridad.index'), 'fa-shield-alt', 'Seguridad') !!}
            {!! $navLink('folios', route('admin.folios.index'), 'fa-hashtag', 'Folios') !!}
        </div>
        <div class="flex flex-col gap-2 border-t border-slate-200 pt-3 sm:flex-row sm:items-center sm:justify-end sm:gap-3 sm:border-t-0 sm:pt-0 lg:border-l lg:border-t-0 lg:pl-4">
            @include('partials.nav-anlux-user-bar')
            <a
                href="{{ route('ordenes.index') }}"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-blue-200 bg-white px-4 py-2 text-sm font-bold text-blue-800 shadow-sm transition hover:border-blue-400 hover:bg-blue-50"
            >
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                &Oacute;rdenes
            </a>
        </div>
    </div>
</nav>
