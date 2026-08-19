@extends('layouts.exacto_guest')

@section('content')
<div class="rounded-lg bg-white p-5 shadow-2xl sm:p-8">
    @if (session('status'))
        <div class="mb-4 rounded-lg bg-emerald-500 p-4 text-center text-sm font-semibold text-white" role="status">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-500 p-4 text-center text-sm font-semibold text-white" role="alert">
            {{ $errors->first() ?: 'Credenciales incorrectas. Intenta de nuevo.' }}
        </div>
    @endif

    @if (request()->boolean('pwd_error'))
        <div class="mb-4 rounded-lg bg-red-500 p-4 text-center text-sm font-semibold text-white" role="alert">
            Contraseña incorrecta. Intenta de nuevo.
        </div>
    @endif

    <div class="mb-8 text-center">
        <img src="{{ asset('legacy/public/img/logo.jpeg') }}?v={{ @filemtime(public_path('legacy/public/img/logo.jpeg')) ?: 1 }}" alt="Exacto" class="mx-auto mb-4 h-14 sm:h-16" width="180" height="64" style="height: 4rem; max-width: 180px; object-fit: contain;">
        <h1 class="text-2xl font-bold text-blue-600 sm:text-3xl">Bienvenido</h1>
        <p class="text-gray-500">Inicia sesión en tu cuenta</p>
    </div>

    <form method="POST" action="{{ route('login') }}" id="loginForm" class="space-y-5" autocomplete="on">
        @csrf
        <input type="text" id="email" name="email" value="{{ old('email') }}" placeholder="Nombre de usuario o correo"
               class="w-full rounded-lg border-2 border-blue-200 bg-blue-50 px-4 py-3 focus:border-blue-600 focus:outline-none" required autofocus autocomplete="username">

        <div class="relative">
            <input type="password" id="password" name="password" placeholder="Contraseña"
                   class="w-full rounded-lg border-2 border-blue-200 bg-blue-50 px-4 py-3 pr-12 focus:border-blue-600 focus:outline-none" required autocomplete="current-password">
            <button type="button" id="togglePassword" tabindex="-1"
                    class="absolute right-4 top-1/2 -translate-y-1/2 text-blue-500 hover:text-blue-700" aria-label="Mostrar u ocultar contraseña">
                <i class="fas fa-eye"></i>
            </button>
        </div>

        <label class="flex items-center">
            <input id="remember_me" type="checkbox" name="remember" class="h-4 w-4 rounded border-blue-300 text-blue-600 focus:ring-blue-500" {{ old('remember') ? 'checked' : '' }}>
            <span class="ml-2 text-sm text-gray-700">{{ __('Recuérdame') }}</span>
        </label>

        <button type="submit" class="w-full rounded-lg bg-blue-600 py-3 font-semibold text-white shadow hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-300">
            Iniciar sesión
        </button>
    </form>

</div>

@php
    $loginToggleJsPath = public_path('legacy/assets/js/login_toggle.js');
    $loginToggleJsV = is_file($loginToggleJsPath) ? filemtime($loginToggleJsPath) : 1;
@endphp
<script src="{{ asset('legacy/assets/js/login_toggle.js') }}?v={{ $loginToggleJsV }}" defer></script>
@endsection
