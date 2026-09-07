@extends('layouts.anlux_guest')

@section('content')
<div id="reset-password-react-root"></div>
<script type="application/json" id="react-page-props">
{!! json_encode([
    'action' => route('password.store'),
    'csrf' => csrf_token(),
    'token' => $request->route('token'),
    'oldEmail' => old('email', $request->email),
    'error' => $errors->any() ? $errors->first() : null,
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
</script>
@vite(['resources/js/auth/reset_password/main.tsx'])
@endsection
