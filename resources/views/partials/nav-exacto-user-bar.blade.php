@if($exactoIsImpersonating ?? false)
    <div class="w-full rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-900 sm:w-auto">
        <i class="fas fa-user-secret mr-1"></i>
        Actuando como: <strong>{{ $nombreTecnicoMostrado ?? '' }}</strong>
        <button type="button" id="navBtnSalirImpersonacion" class="ml-2 inline-flex items-center rounded border border-amber-500 bg-white px-2 py-0.5 text-xs font-bold text-amber-800 hover:bg-amber-100">
            Volver a mi cuenta
        </button>
    </div>
@endif

@if($exactoCanSwitchAccount ?? false)
    @php
        $statusPrefix = static function (string $status): string {
            return match ($status) {
                'online' => '● ',
                'in_use' => '◐ ',
                'offline' => '○ ',
                'inactive' => '✕ ',
                'self' => '→ ',
                default => '',
            };
        };
    @endphp
    <div class="exacto-account-switch-wrap w-full sm:w-auto">
        <label class="sr-only" for="navSelectCuenta">Cambiar de cuenta</label>
        <select
            id="navSelectCuenta"
            class="exacto-account-switch w-full min-w-[220px] rounded-lg border-2 border-blue-300 bg-white px-3 py-2 text-sm font-semibold text-blue-900 sm:w-auto"
            data-current-id="{{ $exactoUserId ?? 0 }}"
        >
            <option value="">— Cambiar de cuenta —</option>
            @foreach($exactoSwitchAccounts ?? [] as $acc)
                <option
                    value="{{ $acc['id'] }}"
                    class="exacto-opt-{{ $acc['status'] }}"
                    @disabled(empty($acc['selectable']))
                    @selected(($acc['status'] ?? '') === 'self')
                >{{ $statusPrefix($acc['status'] ?? '') }}{{ $acc['nombre'] }} — {{ $acc['status_label'] }}</option>
            @endforeach
        </select>
        <div class="exacto-account-switch-legend mt-1 flex flex-wrap gap-x-3 gap-y-0.5 text-[11px] font-medium text-slate-600" aria-hidden="true">
            <span><span class="exacto-dot exacto-dot--online" aria-hidden="true"></span> En línea</span>
            <span><span class="exacto-dot exacto-dot--in_use" aria-hidden="true"></span> En uso</span>
            <span><span class="exacto-dot exacto-dot--offline" aria-hidden="true"></span> Sin conexión</span>
            <span><span class="exacto-dot exacto-dot--inactive" aria-hidden="true"></span> Inactiva</span>
        </div>
    </div>
    @php
        $exactoUserBarCssPath = public_path('legacy/assets/css/exacto_user_bar.css');
        $exactoUserBarCssV = is_file($exactoUserBarCssPath) ? filemtime($exactoUserBarCssPath) : 1;
    @endphp
    @push('styles')
    <link rel="stylesheet" href="{{ asset('legacy/assets/css/exacto_user_bar.css') }}?v={{ $exactoUserBarCssV }}">
    @endpush
@endif

@if(!empty($nombreTecnicoMostrado))
    <p class="mb-0 flex items-center text-lg font-bold text-blue-900 sm:text-xl sm:text-right">
        <span class="text-base font-semibold text-blue-700 sm:text-lg">Técnico:</span>
        <span class="text-blue-600"><i class="fas fa-user-circle ml-1 mr-1"></i></span>{{ $nombreTecnicoMostrado }}
    </p>
@endif

@once
    @include('partials.exacto-ui-modal')
    <script>
        window.EXACTO_IS_ADMIN = @json($exactoIsAdmin ?? false);
        window.EXACTO_IS_TECHNICIAN = @json($exactoIsTechnician ?? false);
        window.EXACTO_IS_IMPERSONATING = @json($exactoIsImpersonating ?? false);
        window.EXACTO_CAN_SWITCH_ACCOUNT = @json($exactoCanSwitchAccount ?? false);
        window.EXACTO_USER_ID = @json($exactoUserId ?? 0);
    </script>
    <script src="{{ asset('legacy/assets/js/exacto_ui.js') }}?v={{ $exactoUiJsV ?? 1 }}"></script>
    <script src="{{ asset('legacy/assets/js/nav_impersonacion.js') }}?v={{ $exactoNavImpV ?? 1 }}"></script>
@endonce
