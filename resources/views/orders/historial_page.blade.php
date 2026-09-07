@extends('layouts.anlux_app')

@section('content')
<body class="bg-blue-50 px-3 py-4 sm:p-6 lg:p-8">
    <div class="mx-auto max-w-7xl rounded-lg bg-white p-4 shadow-lg sm:p-6 lg:p-8">
        @include('partials.nav-app')
        @include('partials.header-flujo-tres')
        <div id="historial-react-root"></div>
    </div>
    @vite(['resources/js/historial/main.tsx'])
</body>
@endsection
