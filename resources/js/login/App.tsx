import { useState } from 'react';
import { readPageProps } from '../shared/http';

type LoginProps = {
  action: string;
  csrf: string;
  oldEmail: string;
  remember: boolean;
  status: string | null;
  error: string | null;
  logoUrl: string;
};

export default function App() {
  const props = readPageProps<LoginProps>() || {
    action: '/login',
    csrf: '',
    oldEmail: '',
    remember: false,
    status: null,
    error: null,
    logoUrl: '',
  };
  const [showPassword, setShowPassword] = useState(false);

  return (
    <div className="rounded-lg bg-white p-5 shadow-2xl sm:p-8">
      {props.status ? (
        <div className="mb-4 rounded-lg bg-emerald-500 p-4 text-center text-sm font-semibold text-white" role="status">
          {props.status}
        </div>
      ) : null}
      {props.error ? (
        <div className="mb-4 rounded-lg bg-red-500 p-4 text-center text-sm font-semibold text-white" role="alert">
          {props.error}
        </div>
      ) : null}

      <div className="mb-8 text-center">
        {props.logoUrl ? (
          <img src={props.logoUrl} alt="Anlux" className="mx-auto mb-4 h-14 sm:h-16" width={180} height={64} style={{ height: '4rem', maxWidth: 180, objectFit: 'contain' }} />
        ) : null}
        <h1 className="text-2xl font-bold text-blue-600 sm:text-3xl">Bienvenido</h1>
        <p className="text-gray-500">Inicia sesión en tu cuenta</p>
      </div>

      <form method="POST" action={props.action} className="space-y-5" autoComplete="on">
        <input type="hidden" name="_token" value={props.csrf} />
        <input
          type="text"
          id="email"
          name="email"
          defaultValue={props.oldEmail}
          placeholder="Nombre de usuario o correo"
          className="w-full rounded-lg border-2 border-blue-200 bg-blue-50 px-4 py-3 focus:border-blue-600 focus:outline-none"
          required
          autoFocus
          autoComplete="username"
        />
        <div className="relative">
          <input
            type={showPassword ? 'text' : 'password'}
            id="password"
            name="password"
            placeholder="Contraseña"
            className="w-full rounded-lg border-2 border-blue-200 bg-blue-50 px-4 py-3 pr-12 focus:border-blue-600 focus:outline-none"
            required
            autoComplete="current-password"
          />
          <button
            type="button"
            tabIndex={-1}
            onClick={() => setShowPassword((v) => !v)}
            className="absolute right-4 top-1/2 -translate-y-1/2 text-blue-500 hover:text-blue-700"
            aria-label="Mostrar u ocultar contraseña"
          >
            <i className={`fas ${showPassword ? 'fa-eye-slash' : 'fa-eye'}`} />
          </button>
        </div>
        <label className="flex items-center">
          <input
            id="remember_me"
            type="checkbox"
            name="remember"
            defaultChecked={props.remember}
            className="h-4 w-4 rounded border-blue-300 text-blue-600 focus:ring-blue-500"
          />
          <span className="ml-2 text-sm text-gray-700">Recuérdame</span>
        </label>
        <button type="submit" className="w-full rounded-lg bg-blue-600 py-3 font-semibold text-white shadow hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-300">
          Iniciar sesión
        </button>
      </form>
    </div>
  );
}
