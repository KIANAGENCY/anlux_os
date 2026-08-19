@extends('layouts.exacto_guest')

@section('content')
<div class="rounded-lg bg-white p-5 shadow-2xl sm:p-8">
    <div class="mb-4 text-center text-sm text-blue-900">
        <p>{{ __('¿Olvidaste tu contraseña? Sin problema: indica tu usuario o correo y te enviaremos un enlace para restablecerla.') }}</p>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg bg-emerald-500 p-4 text-center text-sm font-semibold text-white" role="status">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf
        <div>
            <label for="email" class="block text-sm font-semibold text-blue-900">{{ __('Correo o usuario') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                   class="mt-2 w-full rounded-lg border-2 border-blue-200 bg-blue-50 px-4 py-3 focus:border-blue-600 focus:outline-none"/>
            @error('email')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <button type="submit" class="w-full rounded-lg bg-blue-600 py-3 font-semibold text-white shadow hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-300">
            {{ __('Enviar enlace de restablecimiento') }}
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-blue-900">
        <a class="font-semibold text-blue-600 underline hover:text-blue-800" href="{{ route('login') }}">{{ __('Volver al inicio de sesión') }}</a>
    </p>
</div>
@endsection
