@php
    $adminPageAncho = $adminPageAncho ?? 'max-w-6xl';
@endphp
<div class="mx-auto w-full {{ $adminPageAncho }} px-4 py-6 sm:py-8">
    @include('partials.nav-admin')
    <div class="overflow-hidden rounded-2xl bg-white shadow-xl ring-1 ring-slate-200/80">
        <div class="px-4 py-5 sm:px-6 sm:py-6">
