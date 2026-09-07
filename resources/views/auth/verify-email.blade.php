@extends('layouts.anlux_guest')

@section('content')
<div id="verify-email-react-root"></div>
<script type="application/json" id="react-page-props">
{!! json_encode([
    'csrf' => csrf_token(),
    'resendAction' => route('verification.send'),
    'logoutUrl' => route('logout'),
    'status' => session('status'),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
</script>
@vite(['resources/js/auth/verify_email/main.tsx'])
@endsection
