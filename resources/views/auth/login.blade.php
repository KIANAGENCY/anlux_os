@extends('layouts.anlux_guest')

@section('content')
@php
    $logoUrl = $anluxLogoUrl ?? asset('legacy/public/img/logo.jpeg');
    $errorMsg = $errors->any()
        ? ($errors->first() ?: 'Credenciales incorrectas. Intenta de nuevo.')
        : (request()->boolean('pwd_error') ? 'Contraseña incorrecta. Intenta de nuevo.' : null);
@endphp
<div id="login-react-root"></div>
<script type="application/json" id="react-page-props">
{!! json_encode([
    'action' => route('login'),
    'csrf' => csrf_token(),
    'oldEmail' => old('email', ''),
    'remember' => (bool) old('remember'),
    'status' => session('status'),
    'error' => $errorMsg,
    'logoUrl' => $logoUrl,
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
</script>
@vite(['resources/js/login/main.tsx'])
@endsection
