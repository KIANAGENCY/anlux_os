import { readPageProps } from '../../shared/http';

type Props = {
  csrf: string;
  resendAction: string;
  logoutUrl: string;
  status: string | null;
};

export default function App() {
  const props = readPageProps<Props>() || {
    csrf: '',
    resendAction: '/email/verification-notification',
    logoutUrl: '/logout',
    status: null,
  };

  return (
    <div className="rounded-lg bg-white p-5 shadow-2xl sm:p-8">
      <p className="mb-4 text-sm text-blue-900">
        Gracias por registrarte. Verifica tu correo con el enlace que te enviamos. Si no llegó, puedes reenviarlo.
      </p>
      {props.status === 'verification-link-sent' ? (
        <div className="mb-4 rounded-lg bg-emerald-500 p-4 text-sm font-semibold text-white">
          Se envió un nuevo enlace de verificación.
        </div>
      ) : null}
      <div className="mt-4 flex flex-wrap items-center justify-between gap-3">
        <form method="POST" action={props.resendAction}>
          <input type="hidden" name="_token" value={props.csrf} />
          <button type="submit" className="rounded-lg bg-blue-600 px-5 py-2.5 font-semibold text-white hover:bg-blue-700">
            Reenviar correo
          </button>
        </form>
        <form method="POST" action={props.logoutUrl}>
          <input type="hidden" name="_token" value={props.csrf} />
          <button type="submit" className="text-sm font-semibold text-blue-700 underline hover:text-blue-900">
            Cerrar sesión
          </button>
        </form>
      </div>
    </div>
  );
}
