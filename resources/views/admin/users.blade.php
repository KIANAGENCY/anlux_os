@extends('layouts.exacto_app')

@section('content')
<body class="min-h-screen antialiased bg-slate-100 text-slate-800">
    @include('partials.admin-page-open')
        @include('partials.nav-admin')

        @if(session('success'))
            <div class="px-4 py-3 mb-4 text-sm font-semibold text-emerald-700 bg-emerald-50 rounded-lg border border-emerald-200">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="px-4 py-3 mb-4 text-sm font-semibold text-red-700 bg-red-50 rounded-lg border border-red-200">
                {{ session('error') }}
            </div>
        @endif

        <section class="p-4 bg-blue-50 rounded-r-lg border-l-4 border-blue-700 sm:pl-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-blue-900">Tabla de usuarios</h1>
                    <p class="mt-1 text-sm text-blue-700">Puedes asignar o editar el nombre de usuario y, si lo deseas, cambiar la contraseña.</p>
                </div>
                <div class="px-4 py-2 text-sm font-semibold text-blue-900 bg-white rounded-lg shadow-sm">
                    Total de usuarios: {{ $usuarios->count() }}
                </div>
            </div>
        </section>

        <section class="overflow-x-auto mt-6 rounded-lg border border-blue-100">
            <table class="w-full min-w-[860px] border-collapse text-sm">
                <thead>
                    <tr class="text-white bg-blue-600">
                        <th class="p-3 text-left border">ID</th>
                        <th class="p-3 text-left border">Nombre del técnico</th>
                        @if(!empty($tieneNombreUsuario))
                            <th class="p-3 text-left border">Nombre de usuario</th>
                        @endif
                        <th class="p-3 text-left border">Email</th>
                        <th class="p-3 text-left border">Contraseña</th>
                        <th class="p-3 text-left border">Perfil</th>
                        <th class="p-3 text-center border">Estado</th>
                        <th class="p-3 text-center border">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($usuarios as $usuario)
                        @php
                            $estaActivo = $usuario->isActivo();
                            $colspan = !empty($tieneNombreUsuario) ? 8 : 7;
                        @endphp
                        <tr class="hover:bg-blue-50">
                            <td class="p-3 border">{{ $usuario->id_tecnico }}</td>
                            <td class="p-3 border">{{ $usuario->nombre_tecnico }}</td>
                            @if(!empty($tieneNombreUsuario))
                                <td class="p-3 border">{{ $usuario->nombre_usuario ?: '—' }}</td>
                            @endif
                            <td class="p-3 border">{{ $usuario->correo }}</td>
                            <td class="p-3 font-mono border text-slate-600">######</td>
                            <td class="p-3 border">{{ $usuario->perfil }}</td>
                            <td class="p-3 text-center border">
                                <button
                                    type="button"
                                    class="btn-toggle-activo inline-flex h-10 w-10 items-center justify-center rounded-lg border-2 border-white shadow transition hover:opacity-90 {{ $estaActivo ? 'bg-emerald-500 text-white' : 'bg-red-500 text-white' }}"
                                    data-id="{{ $usuario->id_tecnico }}"
                                    data-activo="{{ $estaActivo ? '1' : '0' }}"
                                    data-url="{{ route('admin.users.toggleActivo', $usuario->id_tecnico) }}"
                                    title="{{ $estaActivo ? 'Activo (clic para desactivar)' : 'Inactivo (clic para activar)' }}"
                                    aria-pressed="{{ $estaActivo ? 'true' : 'false' }}"
                                >
                                    <i class="fas {{ $estaActivo ? 'fa-check' : 'fa-times' }}" aria-hidden="true"></i>
                                </button>
                            </td>
                            <td class="p-3 text-center border">
                                <div class="flex flex-wrap gap-2 justify-center items-center">
                                    <button
                                        type="button"
                                        class="inline-flex gap-2 justify-center items-center px-4 py-2 text-sm font-bold text-white bg-blue-600 rounded-lg btn-editar-usuario hover:bg-blue-700"
                                        data-id="{{ $usuario->id_tecnico }}"
                                        data-nombre="{{ $usuario->nombre_tecnico }}"
                                        data-usuario="{{ $usuario->nombre_usuario ?? '' }}"
                                        data-email="{{ $usuario->correo }}"
                                        data-update-url="{{ route('admin.users.updatePassword', $usuario->id_tecnico) }}"
                                    >
                                        <i class="fas fa-user-edit"></i>
                                        Editar
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex gap-2 justify-center items-center px-4 py-2 text-sm font-bold text-white bg-red-600 rounded-lg btn-eliminar-usuario hover:bg-red-700"
                                        data-id="{{ $usuario->id_tecnico }}"
                                        data-nombre="{{ $usuario->nombre_tecnico }}"
                                        data-delete-url="{{ route('admin.users.destroy', $usuario->id_tecnico) }}"
                                    >
                                        <i class="fas fa-trash"></i>
                                        Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ !empty($tieneNombreUsuario) ? 8 : 7 }}" class="p-4 text-center border text-slate-600">No hay usuarios registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>

    <div id="modalEditarUsuario" class="hidden fixed inset-0 z-[85] items-center justify-center bg-slate-950/70 p-4" role="dialog" aria-modal="true" aria-labelledby="modalEditarUsuarioTitulo">
        <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl">
            <div class="px-5 py-3 border-b border-slate-200">
                <h2 id="modalEditarUsuarioTitulo" class="text-lg font-bold text-center text-blue-900">Editar usuario</h2>
            </div>
            <form id="formEditarUsuario" method="POST" action="">
                @csrf
                <div class="px-5 py-4 space-y-3">
                    <div>
                        <label class="block mb-2 text-sm font-semibold text-blue-900">Nombre del técnico</label>
                        <input id="modalUsuarioNombre" type="text" readonly class="px-4 py-2 w-full rounded-lg border-2 border-blue-200 bg-slate-100 text-slate-700">
                    </div>
                    @if(!empty($tieneNombreUsuario))
                        <div>
                            <label for="modalUsuarioUsername" class="block mb-2 text-sm font-semibold text-blue-900">Nombre de usuario</label>
                            <input id="modalUsuarioUsername" name="nombre_usuario" type="text" required autocomplete="username" maxlength="64" pattern="[A-Za-z0-9._\-]+" class="px-4 py-2 w-full rounded-lg border-2 border-blue-300 focus:border-blue-700 focus:outline-none">
                            <p class="mt-1 text-xs text-slate-600">Para iniciar sesión. Letras, números, punto, guion o guion bajo.</p>
                        </div>
                    @endif
                    <div>
                        <label class="block mb-2 text-sm font-semibold text-blue-900">Email</label>
                        <input id="modalUsuarioEmail" type="text" readonly class="px-4 py-2 w-full rounded-lg border-2 border-blue-200 bg-slate-100 text-slate-700">
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-semibold text-blue-900">Contraseña actual en tabla</label>
                        <input type="text" value="######" readonly class="px-4 py-2 w-full font-mono rounded-lg border-2 border-blue-200 bg-slate-100 text-slate-700">
                    </div>
                    <div>
                        <label for="modalUsuarioPassword" class="block mb-2 text-sm font-semibold text-blue-900">Nueva contraseña <span class="font-normal text-slate-500">(opcional)</span></label>
                        <input id="modalUsuarioPassword" name="password" type="password" class="px-4 py-2 w-full rounded-lg border-2 border-blue-300 focus:border-blue-700 focus:outline-none">
                    </div>
                    <div>
                        <label for="modalUsuarioPasswordConfirm" class="block mb-2 text-sm font-semibold text-blue-900">Confirmar nueva contraseña</label>
                        <input id="modalUsuarioPasswordConfirm" name="confirm_password" type="password" class="px-4 py-2 w-full rounded-lg border-2 border-blue-300 focus:border-blue-700 focus:outline-none">
                    </div>
                </div>
                <div class="flex flex-col-reverse gap-3 px-5 py-3 border-t border-slate-200 sm:flex-row sm:justify-end">
                    <button type="button" id="cerrarModalEditarUsuario" class="px-4 py-2 text-sm font-bold bg-white rounded-lg border-2 border-slate-300 text-slate-700 hover:bg-slate-50">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                        Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    @php
        $adminUsersJsPath = public_path('legacy/assets/js/admin_users.js');
        $adminUsersJsV = is_file($adminUsersJsPath) ? filemtime($adminUsersJsPath) : 1;
    @endphp
    <script src="{{ asset('legacy/assets/js/admin_users.js') }}?v={{ $adminUsersJsV }}" defer></script>
    @include('partials.admin-page-close')
</body>
@endsection
