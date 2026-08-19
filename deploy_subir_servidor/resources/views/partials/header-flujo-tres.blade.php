{{-- Navegación entre secciones (debajo del título). Requiere $nav_activo. --}}
@php
    $navFlujo = $nav_activo ?? '';
    $celdaBase = 'group flex flex-1 flex-row items-center gap-4 px-5 py-4 text-left transition duration-200 sm:flex-col sm:items-center sm:gap-3 sm:px-5 sm:py-7 sm:text-center';
    $celdaActiva = 'bg-gradient-to-br from-blue-600 via-blue-700 to-blue-900 text-white shadow-inner';
    $celdaInactiva = 'bg-white text-blue-900 hover:bg-blue-50 active:bg-blue-100';
@endphp
<div class="mx-auto mt-5 w-full max-w-7xl px-1" aria-label="Ir a otra sección del sistema">
    <div class="flex flex-col divide-y-2 divide-blue-200 overflow-hidden rounded-2xl border-2 border-blue-200/90 bg-white shadow-xl shadow-blue-900/10 ring-1 ring-blue-100 sm:flex-row sm:divide-x-2 sm:divide-y-0">
        <a href="{{ route('ordenes.index') }}"
           class="{{ $celdaBase }} {{ $navFlujo === 'ordenes' ? $celdaActiva : $celdaInactiva }}"
           @if ($navFlujo === 'ordenes') aria-current="page" @endif>
            <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl {{ $navFlujo === 'ordenes' ? 'bg-white/15 ring-1 ring-white/25' : 'bg-blue-100 ring-1 ring-blue-200/60 group-hover:bg-blue-200' }}">
                <i class="fas fa-clipboard-list text-2xl {{ $navFlujo === 'ordenes' ? 'text-white' : 'text-blue-600 group-hover:text-blue-700' }} sm:text-3xl"></i>
            </span>
            <span class="min-w-0 flex-1 sm:flex-none">
                <span class="block text-base font-bold leading-snug sm:text-lg">&#211;rdenes registradas</span>
                @if ($navFlujo !== 'ordenes')
                    <span class="mt-2 inline-flex items-center rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-bold text-white shadow-sm transition group-hover:bg-blue-700">Abrir listado</span>
                @endif
            </span>
        </a>
        <a href="{{ route('orden_servicio.create') }}"
           class="{{ $celdaBase }} {{ $navFlujo === 'orden_servicio' ? $celdaActiva : $celdaInactiva }}"
           @if ($navFlujo === 'orden_servicio') aria-current="page" @endif>
            <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl {{ $navFlujo === 'orden_servicio' ? 'bg-white/15 ring-1 ring-white/25' : 'bg-blue-100 ring-1 ring-blue-200/60 group-hover:bg-blue-200' }}">
                <i class="fas fa-file-signature text-2xl {{ $navFlujo === 'orden_servicio' ? 'text-white' : 'text-blue-600 group-hover:text-blue-700' }} sm:text-3xl"></i>
            </span>
            <span class="min-w-0 flex-1 sm:flex-none">
                <span class="block text-base font-bold leading-snug sm:text-lg">Nueva orden de servicio</span>
                @if ($navFlujo !== 'orden_servicio')
                    <span class="mt-2 inline-flex items-center rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-bold text-white shadow-sm transition group-hover:bg-blue-700">Ir a nueva orden</span>
                @endif
            </span>
        </a>
        <a href="{{ route('historial.index') }}"
           class="{{ $celdaBase }} {{ $navFlujo === 'historial_ordenes' ? $celdaActiva : $celdaInactiva }}"
           @if ($navFlujo === 'historial_ordenes') aria-current="page" @endif>
            <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl {{ $navFlujo === 'historial_ordenes' ? 'bg-white/15 ring-1 ring-white/25' : 'bg-blue-100 ring-1 ring-blue-200/60 group-hover:bg-blue-200' }}">
                <i class="fas fa-file-pdf text-2xl {{ $navFlujo === 'historial_ordenes' ? 'text-white' : 'text-blue-600 group-hover:text-blue-700' }} sm:text-3xl"></i>
            </span>
            <span class="min-w-0 flex-1 sm:flex-none">
                <span class="block text-base font-bold leading-snug sm:text-lg">Historial PDF</span>
                @if ($navFlujo !== 'historial_ordenes')
                    <span class="mt-2 inline-flex items-center rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-bold text-white shadow-sm transition group-hover:bg-blue-700">Ver historial</span>
                @endif
            </span>
        </a>
    </div>
</div>
