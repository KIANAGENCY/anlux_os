@extends('layouts.anlux_app')

@section('content')
<body class="bg-blue-50">
    @php
        $nombreTecnico = session('nombre_tecnico') ?: ($user->nombre_tecnico ?? '');
    @endphp
    <div class="mx-auto max-w-5xl px-3 py-4 sm:px-6">
        @include('partials.nav-app')
        <div id="profile-react-root"></div>
    </div>
    <script type="application/json" id="react-page-props">
        {!! json_encode([
            'csrf' => csrf_token(),
            'name' => old('name', $user->name ?? $user->nombre_tecnico ?? ''),
            'email' => old('email', $user->email ?? $user->correo ?? ''),
            'profileAction' => route('profile.update'),
            'passwordAction' => route('password.update'),
            'destroyAction' => route('profile.destroy'),
            'ordenesUrl' => route('ordenes.index'),
            'status' => session('status'),
            'errors' => [
                'name' => $errors->first('name') ?: null,
                'email' => $errors->first('email') ?: null,
                'current_password' => optional($errors->getBag('updatePassword'))->first('current_password') ?: null,
                'password' => optional($errors->getBag('updatePassword'))->first('password') ?: null,
                'password_confirmation' => optional($errors->getBag('updatePassword'))->first('password_confirmation') ?: null,
                'delete_password' => optional($errors->getBag('userDeletion'))->first('password') ?: null,
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
    </script>
    @vite(['resources/js/profile/main.tsx'])
</body>
@endsection
