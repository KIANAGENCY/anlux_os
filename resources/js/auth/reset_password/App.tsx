import { useState } from 'react';
import { readPageProps } from '../../shared/http';

type Props = {
  action: string;
  csrf: string;
  token: string;
  oldEmail: string;
  error: string | null;
};

export default function App() {
  const props = readPageProps<Props>() || {
    action: '/reset-password',
    csrf: '',
    token: '',
    oldEmail: '',
    error: null,
  };
  const [show, setShow] = useState(false);

  return (
    <div className="rounded-lg bg-white p-5 shadow-2xl sm:p-8">
      <h1 className="mb-6 text-center text-xl font-bold text-blue-700">Nueva contraseña</h1>
      {props.error ? (
        <div className="mb-4 rounded-lg bg-red-500 p-4 text-center text-sm font-semibold text-white" role="alert">
          {props.error}
        </div>
      ) : null}
      <form method="POST" action={props.action} className="space-y-5">
        <input type="hidden" name="_token" value={props.csrf} />
        <input type="hidden" name="token" value={props.token} />
        <div>
          <label className="block text-sm font-semibold text-blue-900" htmlFor="email">Correo electrónico</label>
          <input
            id="email"
            type="email"
            name="email"
            defaultValue={props.oldEmail}
            required
            autoFocus
            autoComplete="username"
            className="mt-2 w-full rounded-lg border-2 border-blue-200 bg-blue-50 px-4 py-3 focus:border-blue-600 focus:outline-none"
          />
        </div>
        <div>
          <label className="block text-sm font-semibold text-blue-900" htmlFor="password">Contraseña</label>
          <div className="relative mt-2">
            <input
              id="password"
              type={show ? 'text' : 'password'}
              name="password"
              required
              autoComplete="new-password"
              className="w-full rounded-lg border-2 border-blue-200 bg-blue-50 px-4 py-3 pr-12 focus:border-blue-600 focus:outline-none"
            />
            <button
              type="button"
              tabIndex={-1}
              onClick={() => setShow((v) => !v)}
              className="absolute right-4 top-1/2 -translate-y-1/2 text-blue-500 hover:text-blue-700"
              aria-label="Mostrar u ocultar contraseña"
            >
              <i className={`fas ${show ? 'fa-eye-slash' : 'fa-eye'}`} />
            </button>
          </div>
        </div>
        <div>
          <label className="block text-sm font-semibold text-blue-900" htmlFor="password_confirmation">Confirmar contraseña</label>
          <input
            id="password_confirmation"
            type="password"
            name="password_confirmation"
            required
            autoComplete="new-password"
            className="mt-2 w-full rounded-lg border-2 border-blue-200 bg-blue-50 px-4 py-3 focus:border-blue-600 focus:outline-none"
          />
        </div>
        <button type="submit" className="w-full rounded-lg bg-blue-600 py-3 font-semibold text-white shadow hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-300">
          Restablecer contraseña
        </button>
      </form>
    </div>
  );
}
