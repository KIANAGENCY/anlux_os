<body class="bg-slate-50 px-3 py-4 sm:p-6">
    <div class="mx-auto w-full max-w-7xl">
        @php
            $modoNav = $reactPageProps['meta']['modo'] ?? 'nueva';
            $nav_activo = ($modoNav === 'nueva') ? 'orden_servicio' : 'ordenes';
        @endphp
        @include('partials.nav-app')

        @include('partials.header-flujo-tres')

        <script type="application/json" id="react-page-props">
            {!! json_encode($reactPageProps ?? [
                'meta' => [
                    'id_orden_c' => 0,
                    'modo' => 'nueva',
                    'folio_preview' => '',
                    'nombre_tecnico' => '',
                    'firmas_deshabilitadas' => false,
                    'csrf' => csrf_token(),
                ],
                'urls' => [
                    'registrar' => '',
                    'reenviar' => '',
                    'salida_temporal' => '',
                    'regreso_temporal' => '',
                    'lock_heartbeat' => '',
                    'lock_release' => '',
                    'ordenes_index' => url('/ordenes'),
                    'pdf' => url('/pdf/orden/{id}'),
                ],
                'catalogs' => [
                    'tipos_servicio' => [],
                    'servicios_sersop' => [],
                    'condiciones_entrega' => [],
                    'estatus_flujo' => ['Recepcion', 'En proceso', 'Terminado', 'Entregado'],
                ],
                'flags' => [
                    'salida_temporal_activa' => false,
                    'motivo_salida_temporal' => '',
                    'fecha_salida_temporal' => '',
                ],
                'orden' => null,
            ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
        </script>

        <div id="orden-react-root" class="mt-3"></div>

        @vite(['resources/js/orden_form/main.tsx'])
    </div>
</body>
