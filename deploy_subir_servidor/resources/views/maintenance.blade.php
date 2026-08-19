@extends('layouts.exacto_app')

@section('content')
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased">
    <main class="flex min-h-screen items-center justify-center px-4 py-10">
        <section class="w-full max-w-xl overflow-hidden rounded-2xl bg-white text-center shadow-2xl ring-1 ring-slate-200" role="alert" aria-live="polite">
            <div class="bg-gradient-to-br from-blue-800 via-blue-700 to-blue-600 px-6 py-8 text-white">
                <img src="{{ asset('legacy/public/img/logo.jpeg') }}" alt="Exacto" class="mx-auto h-16 w-auto rounded-lg bg-white p-2 shadow-md">
                <div class="mx-auto mt-6 flex h-16 w-16 items-center justify-center rounded-full bg-white/15">
                    <i class="fas fa-screwdriver-wrench text-3xl" aria-hidden="true"></i>
                </div>
                <h1 class="mt-4 text-2xl font-extrabold sm:text-3xl">Sistema en mantenimiento</h1>
            </div>
            <div class="px-6 py-8 sm:px-10">
                <p class="whitespace-pre-line text-base leading-7 text-slate-600">{{ $maintenance['message'] }}</p>
                <p class="mt-4 text-sm text-slate-500">Tu cuenta permanece segura. Intenta ingresar nuevamente cuando termine el mantenimiento.</p>
                <form method="POST" action="{{ route('logout') }}" class="mt-7">
                    @csrf
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-700 px-6 py-3 font-bold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-200">
                        <i class="fas fa-arrow-left" aria-hidden="true"></i> Regresar al inicio de sesión
                    </button>
                </form>
            </div>
        </section>
    </main>
</body>
@endsection
