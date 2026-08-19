<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle ?? 'Inicio - Exacto' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('legacy/public/img/exacto-icon-1024.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('legacy/public/img/exacto-icon-1024.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @php
        $homeCssPath = public_path('legacy/assets/css/home.css');
        $homeCssV = is_file($homeCssPath) ? filemtime($homeCssPath) : 1;
    @endphp
    <link rel="stylesheet" href="{{ asset('legacy/assets/css/home.css') }}?v={{ $homeCssV }}">
</head>
<body>
    @php($nombreSesion = session('nombre_tecnico') ?: ($user?->nombre_tecnico ?? ''))

    <div class="page-shell">
        <section class="hero">
            <div class="hero-inner">
                <div>
                    <div class="brand-row">
                        <img src="{{ asset('legacy/public/img/logo.jpeg') }}?v={{ @filemtime(public_path('legacy/public/img/logo.jpeg')) ?: 1 }}" alt="Exacto" class="brand-logo">
                        <span class="eyebrow">
                            <i class="fas fa-lock"></i>
                            Uso interno
                        </span>
                    </div>

                    <h1>Menu principal Exacto</h1>
                    @if ($user)
                        <div class="session-pill">
                            <i class="fas fa-circle-check"></i>
                            Sesion activa: {{ $nombreSesion }}
                        </div>
                    @endif
                </div>

                @if ($user)
                    <div class="hero-panel">
                        <h2>Acceso inmediato</h2>
                        <div class="btn-row">
                            <a href="{{ route('ordenes.index') }}" class="btn btn-primary">
                                <i class="fas fa-grid-2"></i>
                                Abrir sistema
                            </a>
                            <a href="{{ route('profile.edit') }}" class="btn btn-secondary">
                                <i class="fas fa-user-gear"></i>
                                Mi perfil
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </section>

        <div class="content-grid">
            @if ($user)
                <section class="section-card">
                    <div class="section-head">
                        <div>
                            <h2>Modulos principales</h2>
                        </div>
                        <span class="badge">
                            <i class="fas fa-bolt"></i>
                            Acceso rapido
                        </span>
                    </div>

                    <div class="menu-grid">
                        <a href="{{ route('ordenes.index') }}" class="menu-card">
                            <span class="menu-icon icon-blue"><i class="fas fa-clipboard-list"></i></span>
                            <h3>Ordenes</h3>
                            <span class="menu-link">Entrar <i class="fas fa-arrow-right"></i></span>
                        </a>

                        <a href="{{ route('orden_servicio.create') }}" class="menu-card">
                            <span class="menu-icon icon-green"><i class="fas fa-file-circle-plus"></i></span>
                            <h3>Nueva orden</h3>
                            <span class="menu-link">Crear <i class="fas fa-arrow-right"></i></span>
                        </a>

                        <a href="{{ route('historial.index') }}" class="menu-card">
                            <span class="menu-icon icon-amber"><i class="fas fa-clock-rotate-left"></i></span>
                            <h3>Historial</h3>
                            <span class="menu-link">Consultar <i class="fas fa-arrow-right"></i></span>
                        </a>

                        @if ($isAdmin)
                            <a href="{{ route('admin.index') }}" class="menu-card">
                                <span class="menu-icon icon-violet"><i class="fas fa-shield-halved"></i></span>
                                <h3>Panel admin</h3>
                                <span class="menu-link">Abrir panel <i class="fas fa-arrow-right"></i></span>
                            </a>

                            <a href="{{ route('admin.users.index') }}" class="menu-card">
                                <span class="menu-icon icon-sky"><i class="fas fa-users"></i></span>
                                <h3>Usuarios</h3>
                                <span class="menu-link">Administrar <i class="fas fa-arrow-right"></i></span>
                            </a>

                            <a href="{{ route('admin.seguridad.index') }}" class="menu-card">
                                <span class="menu-icon icon-rose"><i class="fas fa-user-shield"></i></span>
                                <h3>Seguridad</h3>
                                <span class="menu-link">Revisar <i class="fas fa-arrow-right"></i></span>
                            </a>
                        @endif
                    </div>
                </section>
            @else
                <section class="section-card">
                    <div class="login-card">
                        <div class="login-copy">
                            <h2>Acceso interno al proyecto</h2>
                            <div class="btn-row" style="margin-top: 18px;">
                                <a href="{{ route('login') }}" class="btn" style="background: var(--primary); color: #fff;">
                                    <i class="fas fa-right-to-bracket"></i>
                                    Ir a inicio de sesion
                                </a>
                            </div>
                        </div>

                        <div class="info-box">
                            <strong>Que puedes hacer aqui</strong>
                            <ul>
                                <li>Entrar</li>
                                <li>Acceder al menu principal</li>
                                <li>Usar esta portada como inicio</li>
                            </ul>
                        </div>
                    </div>
                </section>
            @endif
        </div>

        <div class="footer-note">
            <span>Exacto | Sistema interno de ordenes y administracion</span>
            <nav class="footer-legal" aria-label="Enlaces legales">
                <a href="{{ route('legal.terminos') }}">Terminos y condiciones</a>
                <span class="footer-sep" aria-hidden="true">|</span>
                <a href="{{ route('legal.privacidad') }}">Aviso de privacidad</a>
            </nav>
        </div>
    </div>
</body>
</html>
