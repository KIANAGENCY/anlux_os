@extends('layouts.exacto_app')

@section('content')
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased">
    @include('partials.admin-page-open')
        @include('partials.nav-admin')
        <h1 class="mb-8 text-center text-2xl font-bold text-blue-700 sm:text-3xl">Registro de usuarios</h1>
        @if(session('success'))
            <div class="mb-5 rounded-lg bg-green-500 p-4 text-white">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-5 rounded-lg bg-red-500 p-4 text-white">{{ session('error') }}</div>
        @endif
        <form action="{{ route('admin.registro.store') }}" method="POST" class="space-y-5" id="formRegistroUsuario">
            @csrf
            <div>
                <label for="nombre" class="mb-2 block font-bold text-blue-700">Nombre completo:</label>
                <input type="text" id="nombre" name="nombre" value="{{ old('nombre') }}" required
                       placeholder="Ej. JUAN CARLOS PEREZ"
                       autocomplete="name"
                       class="w-full rounded-lg border border-blue-300 px-3 py-2 uppercase focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300">
            </div>
            <div>
                <label for="nombre_usuario" class="mb-2 block font-bold text-blue-700">Nombre de usuario:</label>
                <p class="mb-2 text-xs text-gray-600">Para iniciar sesión. Letras, números, punto, guion o guion bajo (sin espacios).</p>
                <input type="text" id="nombre_usuario" name="nombre_usuario" value="{{ old('nombre_usuario') }}" required
                       placeholder="Ej. JCARLOS"
                       autocomplete="username" maxlength="64" pattern="[A-Za-z0-9._\-]+"
                       class="w-full rounded-lg border border-blue-300 px-3 py-2 uppercase focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300">
            </div>
            <div>
                <label for="email" class="mb-2 block font-bold text-blue-700">Email:</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required
                       placeholder="Ej. juan.carlos@exacto.com"
                       autocomplete="email"
                       class="w-full rounded-lg border border-blue-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300">
            </div>
            <div>
                <label for="password" class="mb-2 block font-bold text-blue-700">Contraseña:</label>
                <p class="mb-2 text-xs text-gray-600">Mínimo 8 caracteres; use letras y al menos un número o un carácter especial.</p>
                <div class="relative">
                    <input type="password" id="password" name="password" minlength="8" required autocomplete="new-password"
                           placeholder="Escribe tu contraseña"
                           class="w-full rounded-lg border border-blue-300 px-3 py-2 pr-12 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300">
                    <button type="button" class="toggle-password absolute right-3 top-1/2 -translate-y-1/2 text-blue-600 hover:text-blue-800" data-target="password" tabindex="-1" aria-label="Mostrar u ocultar contraseña">
                        <i class="fas fa-eye" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <div>
                <label for="confirm_password" class="mb-2 block font-bold text-blue-700">Confirmar contraseña:</label>
                <div class="relative">
                    <input type="password" id="confirm_password" name="confirm_password" minlength="8" required autocomplete="new-password"
                           placeholder="Confirma tu contraseña"
                           class="w-full rounded-lg border border-blue-300 px-3 py-2 pr-12 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300">
                    <button type="button" class="toggle-password absolute right-3 top-1/2 -translate-y-1/2 text-blue-600 hover:text-blue-800" data-target="confirm_password" tabindex="-1" aria-label="Mostrar u ocultar contraseña">
                        <i class="fas fa-eye" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <div>
                <label for="celular" class="mb-2 block font-bold text-blue-700">Celular:</label>
                <input type="text" id="celular" name="celular" inputmode="numeric" maxlength="15" value="{{ old('celular') }}" required
                       placeholder="Ej. 6121942057"
                       autocomplete="tel"
                       class="w-full rounded-lg border border-blue-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300">
            </div>
            <div>
                <label class="mb-3 block font-bold text-blue-700">Perfil:</label>
                <div class="flex flex-col gap-3 sm:flex-row sm:gap-5">
                    <label class="flex cursor-pointer items-center gap-2">
                        <input type="radio" name="perfil" value="tecnico" {{ old('perfil') === 'tecnico' ? 'checked' : '' }} required>
                        <span class="text-blue-700">Técnico</span>
                    </label>
                    <label class="flex cursor-pointer items-center gap-2">
                        <input type="radio" name="perfil" value="administrador" {{ old('perfil') === 'administrador' ? 'checked' : '' }} required>
                        <span class="text-blue-700">Administrador</span>
                    </label>
                </div>
            </div>
            <button type="submit" class="w-full rounded-lg bg-gradient-to-r from-blue-500 to-blue-700 py-3 font-bold text-white transition-transform hover:-translate-y-1 hover:shadow-lg">Registrarse</button>
        </form>
    @php
        $adminRegistroJsPath = public_path('legacy/assets/js/admin_registro.js');
        $adminRegistroJsV = is_file($adminRegistroJsPath) ? filemtime($adminRegistroJsPath) : 1;
    @endphp
    <script src="{{ asset('legacy/assets/js/admin_registro.js') }}?v={{ $adminRegistroJsV }}" defer></script>
    @include('partials.admin-page-close')
</body>
@endsection
