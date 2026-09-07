@extends('layouts.anlux_guest')

@section('content')
<div id="forgot-password-react-root"></div>
<script type="application/json" id="react-page-props">
{!! json_encode([
    'action' => route('password.email'),
    'csrf' => csrf_token(),
    'oldEmail' => old('email', ''),
    'status' => session('status'),
    'error' => $errors->any() ? $errors->first() : null,
    'loginUrl' => route('login'),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
</script>
@vite(['resources/js/auth/forgot_password/main.tsx'])
@endsection
