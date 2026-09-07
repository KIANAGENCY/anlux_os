import { useMemo, useState } from 'react';
import { csrfToken, readPageProps } from '../../shared/http';

type Usuario = {
  id: number;
  nombre: string;
  usuario: string;
  email: string;
  perfil: string;
  activo: boolean;
  updateUrl: string;
  toggleUrl: string;
  deleteUrl: string;
};

type UsersProps = {
  usuarios: Usuario[];
  tieneNombreUsuario: boolean;
  flashSuccess: string | null;
  flashError: string | null;
};

export default function App() {
  const initial = readPageProps<UsersProps>() || {
    usuarios: [],
    tieneNombreUsuario: true,
    flashSuccess: null,
    flashError: null,
  };
  const [usuarios, setUsuarios] = useState<Usuario[]>(initial.usuarios);
  const [edit, setEdit] = useState<Usuario | null>(null);
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirm, setPasswordConfirm] = useState('');
  const cols = initial.tieneNombreUsuario ? 8 : 7;

  const total = useMemo(() => usuarios.length, [usuarios]);

  const toggleActivo = async (u: Usuario) => {
    try {
      const res = await fetch(u.toggleUrl, {
        method: 'PATCH',
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken(),
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ activo: !u.activo }),
      });
      const data = await res.json() as { success: boolean; activo?: boolean; message?: string };
      if (!res.ok || !data.success) {
        window.alert(data.message || 'No se pudo actualizar el estado.');
        return;
      }
      setUsuarios((list) => list.map((row) => (row.id === u.id ? { ...row, activo: Boolean(data.activo) } : row)));
    } catch {
      window.alert('Error de red al cambiar el estado.');
    }
  };

  const eliminar = async (u: Usuario) => {
    const msg = u.nombre
      ? `¿Eliminar la cuenta de «${u.nombre}»? Esta acción no se puede deshacer.`
      : '¿Eliminar esta cuenta? Esta acción no se puede deshacer.';
    if (!window.confirm(msg)) return;
    try {
      const res = await fetch(u.deleteUrl, {
        method: 'DELETE',
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken(),
          'X-Requested-With': 'XMLHttpRequest',
        },
      });
      const data = await res.json() as { success: boolean; message?: string };
      if (!res.ok || !data.success) {
        window.alert(data.message || 'No se pudo eliminar la cuenta.');
        return;
      }
      setUsuarios((list) => list.filter((row) => row.id !== u.id));
    } catch {
      window.alert('Error de red al eliminar la cuenta.');
    }
  };

  return (
    <>
      {initial.flashSuccess ? (
        <div className="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{initial.flashSuccess}</div>
      ) : null}
      {initial.flashError ? (
        <div className="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{initial.flashError}</div>
      ) : null}

      <section className="rounded-r-lg border-l-4 border-blue-700 bg-blue-50 p-4 sm:pl-6">
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 className="text-2xl font-bold text-blue-900">Tabla de usuarios</h1>
            <p className="mt-1 text-sm text-blue-700">Puedes asignar o editar el nombre de usuario y, si lo deseas, cambiar la contraseña.</p>
          </div>
          <div className="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-blue-900 shadow-sm">
            Total de usuarios:
            {' '}
            {total}
          </div>
        </div>
      </section>

      <section className="mt-6 overflow-x-auto rounded-lg border border-blue-100">
        <table className="w-full min-w-[860px] border-collapse text-sm">
          <thead>
            <tr className="bg-blue-600 text-white">
              <th className="border p-3 text-left">ID</th>
              <th className="border p-3 text-left">Nombre del técnico</th>
              {initial.tieneNombreUsuario ? <th className="border p-3 text-left">Nombre de usuario</th> : null}
              <th className="border p-3 text-left">Email</th>
              <th className="border p-3 text-left">Contraseña</th>
              <th className="border p-3 text-left">Perfil</th>
              <th className="border p-3 text-center">Estado</th>
              <th className="border p-3 text-center">Acciones</th>
            </tr>
          </thead>
          <tbody>
            {usuarios.length === 0 ? (
              <tr><td colSpan={cols} className="border p-4 text-center text-slate-600">No hay usuarios registrados.</td></tr>
            ) : usuarios.map((u) => (
              <tr key={u.id} className="hover:bg-blue-50">
                <td className="border p-3">{u.id}</td>
                <td className="border p-3">{u.nombre}</td>
                {initial.tieneNombreUsuario ? <td className="border p-3">{u.usuario || '—'}</td> : null}
                <td className="border p-3">{u.email}</td>
                <td className="border p-3 font-mono text-slate-600">######</td>
                <td className="border p-3">{u.perfil}</td>
                <td className="border p-3 text-center">
                  <button
                    type="button"
                    onClick={() => void toggleActivo(u)}
                    className={`inline-flex h-10 w-10 items-center justify-center rounded-lg border-2 border-white shadow transition hover:opacity-90 ${u.activo ? 'bg-emerald-500 text-white' : 'bg-red-500 text-white'}`}
                    title={u.activo ? 'Activo (clic para desactivar)' : 'Inactivo (clic para activar)'}
                  >
                    <i className={`fas ${u.activo ? 'fa-check' : 'fa-times'}`} />
                  </button>
                </td>
                <td className="border p-3 text-center">
                  <div className="flex flex-wrap items-center justify-center gap-2">
                    <button
                      type="button"
                      className="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-bold text-white hover:bg-blue-700"
                      onClick={() => {
                        setEdit(u);
                        setUsername(u.usuario || '');
                        setPassword('');
                        setPasswordConfirm('');
                      }}
                    >
                      <i className="fas fa-user-edit" />
                      Editar
                    </button>
                    <button
                      type="button"
                      className="inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-bold text-white hover:bg-red-700"
                      onClick={() => void eliminar(u)}
                    >
                      <i className="fas fa-trash" />
                      Eliminar
                    </button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </section>

      {edit ? (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-3">
          <div className="w-full max-w-md rounded-lg bg-white p-5 shadow-2xl sm:p-8">
            <h3 className="mb-4 text-xl font-bold text-blue-700">Editar usuario</h3>
            <form method="POST" action={edit.updateUrl} className="space-y-4">
              <input type="hidden" name="_token" value={csrfToken()} />
              <div>
                <label className="mb-1 block text-sm font-semibold text-slate-700">Nombre</label>
                <input value={edit.nombre} readOnly className="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2" />
              </div>
              <div>
                <label className="mb-1 block text-sm font-semibold text-slate-700">Email</label>
                <input value={edit.email} readOnly className="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2" />
              </div>
              {initial.tieneNombreUsuario ? (
                <div>
                  <label className="mb-1 block text-sm font-semibold text-slate-700">Nombre de usuario</label>
                  <input
                    name="nombre_usuario"
                    value={username}
                    onChange={(e) => setUsername(e.target.value)}
                    className="w-full rounded-lg border-2 border-blue-300 px-3 py-2"
                    autoComplete="off"
                  />
                </div>
              ) : null}
              <div>
                <label className="mb-1 block text-sm font-semibold text-slate-700">Nueva contraseña (opcional)</label>
                <input
                  type="password"
                  name="password"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  className="w-full rounded-lg border-2 border-blue-300 px-3 py-2"
                  autoComplete="new-password"
                />
              </div>
              <div>
                <label className="mb-1 block text-sm font-semibold text-slate-700">Confirmar contraseña</label>
                <input
                  type="password"
                  name="confirm_password"
                  value={passwordConfirm}
                  onChange={(e) => setPasswordConfirm(e.target.value)}
                  className="w-full rounded-lg border-2 border-blue-300 px-3 py-2"
                  autoComplete="new-password"
                />
              </div>
              <div className="flex justify-end gap-2 pt-2">
                <button type="button" onClick={() => setEdit(null)} className="rounded-lg bg-gray-400 px-4 py-2 text-white hover:bg-gray-500">Cancelar</button>
                <button type="submit" className="rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white hover:bg-blue-700">Guardar</button>
              </div>
            </form>
          </div>
        </div>
      ) : null}
    </>
  );
}
