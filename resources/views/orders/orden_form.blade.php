<body
    class="
      px-3 py-4 sm:p-6 lg:p-8
      bg-blue-50
    "
  >
    <div
      class="
        max-w-7xl w-full

        mx-auto p-4 sm:p-6 lg:p-8
        bg-white
        rounded-lg
        shadow-lg
      "
    >
        @php
            $nav_activo = 'orden_servicio';
        @endphp
        @include('partials.nav-app')

        @include('partials.header-flujo-tres')

        <div id="orden-react-root"></div>

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

        @vite(['resources/js/orden_form/main.tsx'])
    </div>
</body>
