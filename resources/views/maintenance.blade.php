@extends('layouts.exacto_app')

@php
    // Mantén disponible la pantalla aun durante un despliegue parcial.
    $maintenance = $maintenance ?? [
        'enabled' => true,
        'message' => 'Estamos actualizando el sistema. Por favor, vuelve a intentarlo en unos minutos.',
        'updated_at' => null,
    ];
    $canDisableMaintenance = $canDisableMaintenance ?? false;
@endphp

@push('styles')
<style>
    .maintenance-page {
        min-height: 100vh;
        margin: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 32px 16px;
        box-sizing: border-box;
        background: linear-gradient(145deg, #eff6ff 0%, #f8fafc 48%, #e2e8f0 100%);
        color: #1e293b;
        font-family: Arial, Helvetica, sans-serif;
    }
    .maintenance-card {
        width: 100%;
        max-width: 590px;
        overflow: hidden;
        border: 1px solid #dbe4ef;
        border-radius: 22px;
        background: #fff;
        text-align: center;
        box-shadow: 0 24px 60px rgba(15, 23, 42, .16);
    }
    .maintenance-header {
        padding: 32px 24px 30px;
        background: linear-gradient(135deg, #1e40af 0%, #1d4ed8 55%, #2563eb 100%);
        color: #fff;
    }
    .maintenance-logo {
        display: block;
        width: auto;
        height: 62px;
        max-width: 210px;
        margin: 0 auto;
        padding: 8px 14px;
        box-sizing: border-box;
        border-radius: 10px;
        background: #fff;
        object-fit: contain;
        box-shadow: 0 8px 22px rgba(15, 23, 42, .22);
    }
    .maintenance-icon {
        width: 66px;
        height: 66px;
        margin: 22px auto 0;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: rgba(255, 255, 255, .16);
        font-size: 30px;
    }
    .maintenance-title {
        margin: 14px 0 0;
        color: #fff;
        font-size: 29px;
        line-height: 1.2;
        font-weight: 800;
    }
    .maintenance-content { padding: 30px 34px 32px; }
    .maintenance-message {
        margin: 0;
        color: #475569;
        font-size: 17px;
        line-height: 1.65;
        white-space: pre-line;
    }
    .maintenance-note {
        margin: 15px 0 0;
        color: #64748b;
        font-size: 14px;
        line-height: 1.55;
    }
    .maintenance-action { margin: 25px 0 0; }
    .maintenance-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
        min-height: 48px;
        padding: 0 24px;
        border: 0;
        border-radius: 11px;
        color: #fff;
        background: #1d4ed8;
        font: inherit;
        font-size: 16px;
        font-weight: 700;
        text-decoration: none;
        cursor: pointer;
        box-shadow: 0 8px 18px rgba(29, 78, 216, .24);
        transition: transform .15s ease, background-color .15s ease;
    }
    .maintenance-button:hover { background: #1e40af; transform: translateY(-1px); }
    .maintenance-button--success { background: #059669; box-shadow: 0 8px 18px rgba(5, 150, 105, .24); }
    .maintenance-button--success:hover { background: #047857; }
    .maintenance-admin-link {
        display: inline-block;
        margin-top: 18px;
        color: #1d4ed8;
        font-size: 14px;
        font-weight: 700;
        text-decoration: none;
    }
    .maintenance-admin-link:hover { text-decoration: underline; }
    @media (max-width: 520px) {
        .maintenance-page { padding: 18px 12px; }
        .maintenance-card { border-radius: 17px; }
        .maintenance-header { padding: 26px 18px 24px; }
        .maintenance-logo { height: 54px; }
        .maintenance-icon { width: 58px; height: 58px; font-size: 26px; }
        .maintenance-title { font-size: 24px; }
        .maintenance-content { padding: 25px 20px 27px; }
        .maintenance-message { font-size: 16px; }
        .maintenance-button { width: 100%; padding: 0 14px; }
    }
</style>
@endpush

@section('content')
<body class="maintenance-page">
    <main>
        <section class="maintenance-card" role="alert" aria-live="polite">
            <div class="maintenance-header">
                <img
                    src="{{ asset('legacy/public/img/logo.jpeg') }}"
                    alt="Exacto"
                    class="maintenance-logo"
                >
                <div class="maintenance-icon">
                    <i class="fas fa-screwdriver-wrench" aria-hidden="true"></i>
                </div>
                <h1 class="maintenance-title">Sistema en mantenimiento</h1>
            </div>

            <div class="maintenance-content">
                <p class="maintenance-message">{{ $maintenance['message'] }}</p>
                <p class="maintenance-note">Tu cuenta permanece segura. Intenta ingresar nuevamente cuando termine el mantenimiento.</p>

                @if($canDisableMaintenance)
                    <form method="POST" action="{{ route('admin.maintenance.update') }}" class="maintenance-action">
                        @csrf
                        <input type="hidden" name="enabled" value="0">
                        <button type="submit" class="maintenance-button maintenance-button--success">
                            <i class="fas fa-play" aria-hidden="true"></i>
                            Desactivar mantenimiento
                        </button>
                    </form>
                    <a href="{{ route('admin.index') }}" class="maintenance-admin-link">
                        Volver al panel de administración
                    </a>
                @else
                    <form method="POST" action="{{ route('logout') }}" class="maintenance-action">
                        @csrf
                        <button type="submit" class="maintenance-button">
                            <i class="fas fa-arrow-left" aria-hidden="true"></i>
                            Regresar al inicio de sesión
                        </button>
                    </form>
                @endif
            </div>
        </section>
    </main>
</body>
@endsection
