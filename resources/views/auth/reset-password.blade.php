@extends('layouts.exacto_guest')

@section('content')
<div class="rounded-lg bg-white p-5 shadow-2xl sm:p-8">
    <h1 class="mb-6 text-center text-xl font-bold text-blue-700">{{ __('Nueva contraseña') }}</h1>

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-500 p-4 text-center text-sm font-semibold text-white" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <label class="block text-sm font-semibold text-blue-900" for="email">{{ __('Correo electrónico') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username"
                   class="mt-2 w-full rounded-lg border-2 border-blue-200 bg-blue-50 px-4 py-3 focus:border-blue-600 focus:outline-none" />
            @error('email')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-semibold text-blue-900" for="password">{{ __('Contraseña') }}</label>
            <input id="password" type="password" name="password" required autocomplete="new-password"
                   class="mt-2 w-full rounded-lg border-2 border-blue-200 bg-blue-50 px-4 py-3 focus:border-blue-600 focus:outline-none" />
            @error('password')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-semibold text-blue-900" for="password_confirmation">{{ __('Confirmar contraseña') }}</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                   class="mt-2 w-full rounded-lg border-2 border-blue-200 bg-blue-50 px-4 py-3 focus:border-blue-600 focus:outline-none" />
            @error('password_confirmation')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="w-full rounded-lg bg-blue-600 py-3 font-semibold text-white shadow hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-300">
            {{ __('Restablecer contraseña') }}
        </button>
    </form>
</div>
@endsection
