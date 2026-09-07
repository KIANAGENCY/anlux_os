import { readPageProps } from '../../shared/http';

type Props = {
  action: string;
  csrf: string;
  oldEmail: string;
  status: string | null;
  error: string | null;
  loginUrl: string;
};

export default function App() {
  const props = readPageProps<Props>() || {
    action: '/forgot-password',
    csrf: '',
    oldEmail: '',
    status: null,
    error: null,
    loginUrl: '/login',
  };

  return (
    <div className="rounded-lg bg-white p-5 shadow-2xl sm:p-8">
      <div className="mb-4 text-center text-sm text-blue-900">
        <p>¿Olvidaste tu contraseña? Sin problema: indica tu usuario o correo y te enviaremos un enlace para restablecerla.</p>
      </div>
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
      <form method="POST" action={props.action} className="space-y-5">
        <input type="hidden" name="_token" value={props.csrf} />
        <div>
          <label htmlFor="email" className="block text-sm font-semibold text-blue-900">Correo o usuario</label>
          <input
            id="email"
            type="email"
            name="email"
            defaultValue={props.oldEmail}
            required
            autoFocus
            className="mt-2 w-full rounded-lg border-2 border-blue-200 bg-blue-50 px-4 py-3 focus:border-blue-600 focus:outline-none"
          />
        </div>
        <button type="submit" className="w-full rounded-lg bg-blue-600 py-3 font-semibold text-white shadow hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-300">
          Enviar enlace de restablecimiento
        </button>
      </form>
      <p className="mt-6 text-center text-sm text-blue-900">
        <a className="font-semibold text-blue-600 underline hover:text-blue-800" href={props.loginUrl}>
          Volver al inicio de sesión
        </a>
      </p>
    </div>
  );
}
