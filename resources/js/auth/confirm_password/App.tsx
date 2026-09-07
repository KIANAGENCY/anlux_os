import { readPageProps } from '../../shared/http';

type Props = {
  action: string;
  csrf: string;
  error: string | null;
};

export default function App() {
  const props = readPageProps<Props>() || { action: '/confirm-password', csrf: '', error: null };

  return (
    <div className="rounded-lg bg-white p-5 shadow-2xl sm:p-8">
      <p className="mb-4 text-sm text-blue-900">
        Área segura. Confirma tu contraseña antes de continuar.
      </p>
      {props.error ? (
        <div className="mb-4 rounded-lg bg-red-500 p-4 text-center text-sm font-semibold text-white">{props.error}</div>
      ) : null}
      <form method="POST" action={props.action} className="space-y-5">
        <input type="hidden" name="_token" value={props.csrf} />
        <div>
          <label htmlFor="password" className="block text-sm font-semibold text-blue-900">Contraseña</label>
          <input
            id="password"
            type="password"
            name="password"
            required
            autoComplete="current-password"
            className="mt-2 w-full rounded-lg border-2 border-blue-200 bg-blue-50 px-4 py-3 focus:border-blue-600 focus:outline-none"
          />
        </div>
        <button type="submit" className="w-full rounded-lg bg-blue-600 py-3 font-semibold text-white hover:bg-blue-700">
          Confirmar
        </button>
      </form>
    </div>
  );
}
