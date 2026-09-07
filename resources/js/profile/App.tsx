import { useState } from 'react';
import { readPageProps } from '../shared/http';

type Props = {
  csrf: string;
  name: string;
  email: string;
  profileAction: string;
  passwordAction: string;
  destroyAction: string;
  ordenesUrl: string;
  status: string | null;
  errors: {
    name?: string;
    email?: string;
    current_password?: string;
    password?: string;
    password_confirmation?: string;
    delete_password?: string;
  };
};

export default function App() {
  const props = readPageProps<Props>() || {
    csrf: '',
    name: '',
    email: '',
    profileAction: '/profile',
    passwordAction: '/password',
    destroyAction: '/profile',
    ordenesUrl: '/ordenes',
    status: null,
    errors: {},
  };
  const [confirmDelete, setConfirmDelete] = useState(Boolean(props.errors.delete_password));

  return (
    <div className="mx-auto max-w-3xl space-y-6 px-4 py-8 sm:px-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <h1 className="text-2xl font-bold text-blue-900">Mi perfil</h1>
        <a href={props.ordenesUrl} className="rounded-lg border-2 border-blue-300 bg-white px-4 py-2 text-sm font-bold text-blue-800 hover:bg-blue-50">
          <i className="fas fa-arrow-left mr-2" />
          Volver a órdenes
        </a>
      </div>

      {props.status === 'profile-updated' || props.status === 'password-updated' ? (
        <div className="rounded-lg bg-emerald-500 px-4 py-3 text-sm font-semibold text-white">Guardado correctamente.</div>
      ) : null}

      <section className="rounded-xl border border-blue-100 bg-white p-5 shadow-sm sm:p-8">
        <h2 className="text-lg font-bold text-blue-900">Información del perfil</h2>
        <p className="mt-1 text-sm text-slate-600">Actualiza tu nombre y correo de acceso.</p>
        <form method="POST" action={props.profileAction} className="mt-6 space-y-4">
          <input type="hidden" name="_token" value={props.csrf} />
          <input type="hidden" name="_method" value="PATCH" />
          <div>
            <label htmlFor="name" className="block text-sm font-semibold text-blue-900">Nombre</label>
            <input
              id="name"
              name="name"
              type="text"
              required
              defaultValue={props.name}
              className="mt-1 w-full rounded-lg border-2 border-blue-200 bg-blue-50 px-4 py-2.5 focus:border-blue-600 focus:outline-none"
            />
            {props.errors.name ? <p className="mt-1 text-sm text-red-600">{props.errors.name}</p> : null}
          </div>
          <div>
            <label htmlFor="email" className="block text-sm font-semibold text-blue-900">Correo</label>
            <input
              id="email"
              name="email"
              type="email"
              required
              defaultValue={props.email}
              className="mt-1 w-full rounded-lg border-2 border-blue-200 bg-blue-50 px-4 py-2.5 focus:border-blue-600 focus:outline-none"
            />
            {props.errors.email ? <p className="mt-1 text-sm text-red-600">{props.errors.email}</p> : null}
          </div>
          <button type="submit" className="rounded-lg bg-blue-600 px-5 py-2.5 font-semibold text-white hover:bg-blue-700">Guardar</button>
        </form>
      </section>

      <section className="rounded-xl border border-blue-100 bg-white p-5 shadow-sm sm:p-8">
        <h2 className="text-lg font-bold text-blue-900">Cambiar contraseña</h2>
        <p className="mt-1 text-sm text-slate-600">Usa una contraseña larga y segura.</p>
        <form method="POST" action={props.passwordAction} className="mt-6 space-y-4">
          <input type="hidden" name="_token" value={props.csrf} />
          <input type="hidden" name="_method" value="PUT" />
          <div>
            <label htmlFor="current_password" className="block text-sm font-semibold text-blue-900">Contraseña actual</label>
            <input
              id="current_password"
              name="current_password"
              type="password"
              autoComplete="current-password"
              className="mt-1 w-full rounded-lg border-2 border-blue-200 bg-blue-50 px-4 py-2.5 focus:border-blue-600 focus:outline-none"
            />
            {props.errors.current_password ? <p className="mt-1 text-sm text-red-600">{props.errors.current_password}</p> : null}
          </div>
          <div>
            <label htmlFor="password" className="block text-sm font-semibold text-blue-900">Nueva contraseña</label>
            <input
              id="password"
              name="password"
              type="password"
              autoComplete="new-password"
              className="mt-1 w-full rounded-lg border-2 border-blue-200 bg-blue-50 px-4 py-2.5 focus:border-blue-600 focus:outline-none"
            />
            {props.errors.password ? <p className="mt-1 text-sm text-red-600">{props.errors.password}</p> : null}
          </div>
          <div>
            <label htmlFor="password_confirmation" className="block text-sm font-semibold text-blue-900">Confirmar contraseña</label>
            <input
              id="password_confirmation"
              name="password_confirmation"
              type="password"
              autoComplete="new-password"
              className="mt-1 w-full rounded-lg border-2 border-blue-200 bg-blue-50 px-4 py-2.5 focus:border-blue-600 focus:outline-none"
            />
            {props.errors.password_confirmation ? <p className="mt-1 text-sm text-red-600">{props.errors.password_confirmation}</p> : null}
          </div>
          <button type="submit" className="rounded-lg bg-blue-600 px-5 py-2.5 font-semibold text-white hover:bg-blue-700">Guardar</button>
        </form>
      </section>

      <section className="rounded-xl border border-red-200 bg-white p-5 shadow-sm sm:p-8">
        <h2 className="text-lg font-bold text-red-800">Eliminar cuenta</h2>
        <p className="mt-1 text-sm text-slate-600">
          Una vez eliminada, no se puede recuperar. Confirma con tu contraseña.
        </p>
        <button
          type="button"
          onClick={() => setConfirmDelete(true)}
          className="mt-4 rounded-lg bg-red-600 px-5 py-2.5 font-semibold text-white hover:bg-red-700"
        >
          Eliminar cuenta
        </button>
        {confirmDelete ? (
          <form method="POST" action={props.destroyAction} className="mt-6 space-y-4 rounded-lg border border-red-200 bg-red-50 p-4">
            <input type="hidden" name="_token" value={props.csrf} />
            <input type="hidden" name="_method" value="DELETE" />
            <p className="text-sm font-semibold text-red-900">¿Seguro que quieres eliminar tu cuenta?</p>
            <input
              name="password"
              type="password"
              placeholder="Contraseña"
              className="w-full rounded-lg border-2 border-red-200 bg-white px-4 py-2.5 focus:border-red-500 focus:outline-none"
            />
            {props.errors.delete_password ? <p className="text-sm text-red-600">{props.errors.delete_password}</p> : null}
            <div className="flex flex-wrap gap-2">
              <button type="button" onClick={() => setConfirmDelete(false)} className="rounded-lg border-2 border-slate-300 bg-white px-4 py-2 text-sm font-semibold">
                Cancelar
              </button>
              <button type="submit" className="rounded-lg bg-red-600 px-4 py-2 text-sm font-bold text-white hover:bg-red-700">
                Confirmar eliminación
              </button>
            </div>
          </form>
        ) : null}
      </section>
    </div>
  );
}
