@extends('layouts.anlux_guest')

@section('content')
<div id="confirm-password-react-root"></div>
<script type="application/json" id="react-page-props">
{!! json_encode([
    'action' => route('password.confirm'),
    'csrf' => csrf_token(),
    'error' => $errors->any() ? $errors->first() : null,
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
</script>
@vite(['resources/js/auth/confirm_password/main.tsx'])
@endsection
