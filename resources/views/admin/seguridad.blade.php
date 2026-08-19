@extends('layouts.exacto_app')

@section('content')
@php
    $adminPageAncho = 'max-w-6xl';
@endphp
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased">
    @include('partials.admin-page-open')
        @include('partials.nav-admin')
        <div class="mb-4 border-b-4 border-blue-700 pb-3">
            <h1 class="text-2xl font-bold text-blue-800 sm:text-3xl"><i class="fas fa-shield-alt mr-2"></i>Seguridad / Actividad</h1>
            <p class="mt-1 text-sm text-blue-900">Vista compacta con resumen y detalle de eventos de seguridad.</p>
        </div>

        <section class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-lg border {{ $secretActivo ? 'border-emerald-300 bg-emerald-50' : 'border-red-300 bg-red-50' }} p-3">
                <p class="text-sm font-bold {{ $secretActivo ? 'text-emerald-800' : 'text-red-800' }}">Secreto de encriptaci&oacute;n</p>
                <p class="mt-1 text-xl font-black {{ $secretActivo ? 'text-emerald-700' : 'text-red-700' }}">{{ $secretActivo ? 'Activo' : 'Falta' }}</p>
            </div>
            <div class="rounded-lg border border-blue-200 bg-blue-50 p-3"><p class="text-sm font-bold text-blue-800">&Oacute;rdenes</p><p class="mt-1 text-xl font-black text-blue-700">{{ $totalOrdenes }}</p></div>
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3"><p class="text-sm font-bold text-slate-800">Actividad 24h</p><p class="mt-1 text-xl font-black text-slate-700">{{ $actividad24h }}</p></div>
            <div class="rounded-lg border border-red-200 bg-red-50 p-3"><p class="text-sm font-bold text-red-800">Alertas/Bloqueos 24h</p><p class="mt-1 text-xl font-black text-red-700">{{ $alertas24h }} / {{ $bloqueos24h }}</p></div>
        </section>

        <section class="mb-4 rounded-lg border border-slate-200 bg-white p-4">
            <h2 class="mb-3 text-lg font-bold text-slate-900">Actividad de seguridad</h2>
            <form method="get" action="{{ route('admin.seguridad.index') }}" class="mb-4 grid gap-3 rounded-lg border border-slate-100 bg-slate-50 p-3 sm:grid-cols-2 lg:grid-cols-4">
                <label class="block text-xs font-bold uppercase tracking-wide text-slate-600">
                    Severidad
                    <select name="severity" class="mt-1 w-full rounded border border-slate-300 px-2 py-2 text-sm">
                        <option value="">Todos</option>
                        <option value="info" {{ $severity === 'info' ? 'selected' : '' }}>info</option>
                        <option value="warning" {{ $severity === 'warning' ? 'selected' : '' }}>warning</option>
                        <option value="critical" {{ $severity === 'critical' ? 'selected' : '' }}>critical</option>
                    </select>
                </label>
                <label class="block text-xs font-bold uppercase tracking-wide text-slate-600">
                    Tipo de evento
                    <select name="event_type" class="mt-1 w-full rounded border border-slate-300 px-2 py-2 text-sm font-mono">
                        <option value="">Todos</option>
                        @foreach($eventTypes as $ev)
                            @php $type = (string)($ev->event_type ?? ''); @endphp
                            <option value="{{ $type }}" {{ $eventType === $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-xs font-bold uppercase tracking-wide text-slate-600">
                    IP
                    <input type="text" name="ip" value="{{ $ip }}" placeholder="Igual o %comod&iacute;n%" class="mt-1 w-full rounded border border-slate-300 px-2 py-2 text-sm font-mono">
                </label>
                <div class="flex flex-wrap items-end gap-2">
                    <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-bold text-white hover:bg-slate-900">Aplicar filtros</button>
                    <a href="{{ route('admin.seguridad.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Limpiar</a>
                </div>
            </form>
            <div class="overflow-x-auto rounded-lg border border-slate-100">
                <table class="w-full border-collapse text-sm" style="min-width: 980px;">
                    <thead>
                        <tr class="bg-slate-700 text-white">
                            <th class="border p-3 text-left">Fecha</th>
                            <th class="border p-3 text-left">Evento</th>
                            <th class="border p-3 text-left">Categor&iacute;a</th>
                            <th class="border p-3 text-left">Severidad</th>
                            <th class="border p-3 text-left">Usuario</th>
                            <th class="border p-3 text-left">IP</th>
                            <th class="border p-3 text-left">Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recent as $event)
                            @php $ev = (array)$event; @endphp
                            <tr class="align-top hover:bg-slate-50">
                                <td class="border p-3 whitespace-nowrap">{{ $ev['created_at'] ?? '' }}</td>
                                <td class="border p-3">
                                    <p class="font-semibold text-slate-900">{{ $ev['event_label'] ?? ($ev['event_type'] ?? '') }}</p>
                                    <p class="text-xs text-slate-500 font-mono">{{ $ev['event_type'] ?? '' }}</p>
                                    <p class="mt-1 text-xs text-slate-600">{{ $ev['event_explanation'] ?? '' }}</p>
                                </td>
                                <td class="border p-3">
                                    <span class="rounded bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">{{ $ev['event_category'] ?? 'otro' }}</span>
                                </td>
                                <td class="border p-3">{{ $ev['severity'] ?? '' }}</td>
                                <td class="border p-3">{{ $ev['usuario'] ?? '' }}</td>
                                <td class="border p-3 font-mono text-xs">{{ $ev['ip'] ?? '' }}</td>
                                <td class="border p-3">
                                    <p class="text-xs text-slate-700">{{ $ev['details_preview'] ?? '' }}</p>
                                    @if(!empty($ev['uri']))
                                        <p class="mt-1 text-[11px] text-slate-500 break-all">URI: {{ $ev['uri'] }}</p>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="border p-3 text-center">No hay eventos de seguridad registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="mt-4 text-xs text-slate-600">Eventos totales almacenados: {{ $totalActividades }}.</p>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-4">
            <h2 class="mb-3 text-lg font-bold text-slate-900">IPs sospechosas (24h)</h2>
            <div class="overflow-x-auto rounded-lg border border-slate-100">
                <table class="w-full border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-700 text-white">
                            <th class="border p-2 text-left">IP</th>
                            <th class="border p-2 text-left">Eventos</th>
                            <th class="border p-2 text-left">Fallos login</th>
                            <th class="border p-2 text-left">Bloqueos</th>
                            <th class="border p-2 text-left">&Uacute;ltimo evento</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($suspiciousIps as $ipRow)
                            @php $r = (array) $ipRow; @endphp
                            <tr class="hover:bg-slate-50">
                                <td class="border p-2 font-mono text-xs">{{ $r['ip'] ?? '' }}</td>
                                <td class="border p-2">{{ $r['eventos'] ?? 0 }}</td>
                                <td class="border p-2">{{ $r['fallos_login'] ?? 0 }}</td>
                                <td class="border p-2">{{ $r['bloqueos'] ?? 0 }}</td>
                                <td class="border p-2">{{ $r['ultimo_evento'] ?? '' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="border p-3 text-center text-slate-600">Sin IPs sospechosas en las &uacute;ltimas 24 horas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @include('partials.admin-page-close')
</body>
@endsection
