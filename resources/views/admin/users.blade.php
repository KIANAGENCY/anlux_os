@extends('layouts.anlux_app')

@section('content')
<body class="min-h-screen antialiased bg-slate-100 text-slate-800">
    @include('partials.admin-page-open')

        @php
            $usuariosProps = [
                'tieneNombreUsuario' => !empty($tieneNombreUsuario),
                'flashSuccess' => session('success'),
                'flashError' => session('error'),
                'usuarios' => $usuarios->map(static function ($u) {
                    return [
                        'id' => (int) $u->id_tecnico,
                        'nombre' => (string) $u->nombre_tecnico,
                        'usuario' => (string) ($u->nombre_usuario ?? ''),
                        'email' => (string) $u->correo,
                        'perfil' => (string) $u->perfil,
                        'activo' => method_exists($u, 'isActivo') ? (bool) $u->isActivo() : true,
                        'updateUrl' => route('admin.users.updatePassword', $u->id_tecnico),
                        'toggleUrl' => route('admin.users.toggleActivo', $u->id_tecnico),
                        'deleteUrl' => route('admin.users.destroy', $u->id_tecnico),
                    ];
                })->values()->all(),
            ];
        @endphp
        <div id="admin-users-react-root"></div>
        <script type="application/json" id="react-page-props">
            {!! json_encode($usuariosProps, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
        </script>
        @vite(['resources/js/admin/users/main.tsx'])
    @include('partials.admin-page-close')
</body>
@endsection
