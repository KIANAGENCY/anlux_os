@extends('layouts.legal')

@section('content')
    <div id="legal-react-root"></div>
    <script type="application/json" id="react-page-props">
        {!! json_encode($reactPageProps ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
    </script>
    @vite(['resources/js/legal/main.tsx'])
@endsection
