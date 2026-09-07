@extends('layouts.anlux_app')

@php
    $ordenesCssPath = public_path('legacy/assets/css/ordenes.css');
    $ordenesCssV = is_file($ordenesCssPath) ? filemtime($ordenesCssPath) : 1;
@endphp
@push('styles')
    <link rel="stylesheet" href="{{ asset('legacy/assets/css/ordenes.css') }}?v={{ $ordenesCssV }}">
    @vite(['resources/js/ordenes/main.tsx'])
@endpush

@section('content')
<body class="bg-blue-50 px-2 py-3 sm:px-3 sm:py-4 lg:px-4 lg:py-6">
    <div class="mx-auto w-full max-w-[1800px] rounded-lg bg-white p-3 shadow-lg sm:p-5 lg:p-6">
        @include('partials.nav-app')

        @if(session('error'))
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                {{ session('error') }}
            </div>
        @endif

        @include('partials.header-flujo-tres')

        {{-- Fase 1: listado de órdenes en React + TypeScript --}}
        <div id="ordenes-react-root"></div>
    </div>
</body>
@endsection
