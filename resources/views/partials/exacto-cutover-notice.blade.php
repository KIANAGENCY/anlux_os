@php
    $variant = $variant ?? 'app';
@endphp

@php
    $banner = trim((string) config('exacto.cutover_banner'));
    $showHatch = (bool) config('exacto.show_legacy_escape_hatch');
    $legacyRoutes = \App\Support\LegacyAppShortcut::escapeRoutes();
@endphp

@if($banner !== '' || ($showHatch && $legacyRoutes !== []))
    @php
        $guest = $variant === 'guest';
        $bannerBox = $guest
            ? 'rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm leading-snug text-amber-950'
            : 'rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm leading-snug text-amber-950 sm:px-4';
        $hatchBox = $guest
            ? 'mt-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700'
            : 'mt-3 rounded-lg border border-blue-200 bg-blue-50/80 px-3 py-2 text-xs text-blue-900 sm:px-4';
    @endphp
    <div class="{{ $guest ? '' : 'w-full' }}">
        @if($banner !== '')
            <div class="{{ $bannerBox }}" role="status">
                {!! nl2br(e($banner)) !!}
            </div>
        @endif

        @if($showHatch && $legacyRoutes !== [])
            <div class="{{ $hatchBox }}">
                <p class="mb-2 font-semibold">
                    Aplicación anterior (sesión independiente)
                </p>
                <p class="mb-2 leading-relaxed">
                    Si necesitas volver al PHP clásico mientras validamos Laravel, usa estos enlaces. Debes iniciar sesión en el sistema anterior; la sesión de Laravel no se comparte.
                </p>
                <ul class="flex flex-col gap-1.5 sm:flex-row sm:flex-wrap sm:gap-x-4 sm:gap-y-1">
                    @foreach($legacyRoutes as $r)
                        <li>
                            <a href="{{ $r['href'] }}" target="_blank" rel="noopener noreferrer" class="{{ $guest ? 'text-indigo-700 underline hover:text-indigo-900' : 'font-medium text-blue-800 underline hover:text-blue-950' }}">
                                {{ $r['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endif
