@extends('layouts.anlux_app')

@section('content')
@php
    $logoUrl = $anluxLogoUrl ?? asset('legacy/public/img/logo.jpeg');
    $maintenance = $maintenance ?? [
        'enabled' => true,
        'message' => 'Estamos actualizando el sistema. Por favor, vuelve a intentarlo en unos minutos.',
        'updated_at' => null,
    ];
    $canDisableMaintenance = $canDisableMaintenance ?? false;
@endphp
<body>
    <div id="maintenance-react-root">
        {{-- Fallback operativo: React reemplaza este contenido al montar. --}}
        <main class="flex min-h-screen items-center justify-center px-4 py-8" style="background:linear-gradient(145deg,#eff6ff 0%,#f8fafc 48%,#e2e8f0 100%)">
            <section class="w-full max-w-xl overflow-hidden rounded-2xl border border-slate-200 bg-white text-center shadow-2xl" role="alert" aria-live="polite">
                <div class="bg-blue-700 px-6 py-8 text-white">
                    <img src="{{ $logoUrl }}" alt="Anlux" class="mx-auto mb-5 h-14 max-w-[210px] rounded-lg bg-white object-contain px-3 py-2 shadow-lg">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-white/20 text-3xl">
                        <i class="fas fa-screwdriver-wrench" aria-hidden="true"></i>
                    </div>
                    <h1 class="mt-4 text-2xl font-extrabold sm:text-3xl">Sistema en mantenimiento</h1>
                </div>
                <div class="px-6 py-8 sm:px-8">
                    <p class="whitespace-pre-line text-base leading-relaxed text-slate-600 sm:text-lg">{{ $maintenance['message'] }}</p>
                    <p class="mt-4 text-sm text-slate-500">Tu cuenta permanece segura. Intenta ingresar nuevamente cuando termine el mantenimiento.</p>
                    @if($canDisableMaintenance)
                        <form method="POST" action="{{ route('admin.maintenance.update') }}" class="mt-6">
                            @csrf
                            <input type="hidden" name="enabled" value="0">
                            <button type="submit" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-emerald-600 px-6 font-bold text-white shadow-lg">
                                <i class="fas fa-play" aria-hidden="true"></i>
                                Desactivar mantenimiento
                            </button>
                        </form>
                        <a href="{{ route('admin.index') }}" class="mt-4 inline-block text-sm font-bold text-blue-700 hover:underline">Volver al panel de administración</a>
                    @else
                        <form method="POST" action="{{ route('logout') }}" class="mt-6">
                            @csrf
                            <button type="submit" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-blue-700 px-6 font-bold text-white shadow-lg">
                                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                Regresar al inicio de sesión
                            </button>
                        </form>
                    @endif
                </div>
            </section>
        </main>
    </div>
    <script type="application/json" id="react-page-props">
        {!! json_encode([
            'csrf' => csrf_token(),
            'logoUrl' => $logoUrl,
            'message' => (string) ($maintenance['message'] ?? ''),
            'canDisable' => (bool) $canDisableMaintenance,
            'disableAction' => route('admin.maintenance.update'),
            'adminUrl' => route('admin.index'),
            'logoutUrl' => route('logout'),
        ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
    </script>
    @vite(['resources/js/maintenance/main.tsx'])
</body>
@endsection
