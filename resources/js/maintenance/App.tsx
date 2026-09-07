import { readPageProps } from '../shared/http';

type Props = {
  csrf: string;
  logoUrl: string;
  message: string;
  canDisable: boolean;
  disableAction: string;
  adminUrl: string;
  logoutUrl: string;
};

export default function App() {
  const props = readPageProps<Props>() || {
    csrf: '',
    logoUrl: '',
    message: 'Estamos actualizando el sistema. Por favor, vuelve a intentarlo en unos minutos.',
    canDisable: false,
    disableAction: '/admin/maintenance',
    adminUrl: '/admin',
    logoutUrl: '/logout',
  };

  return (
    <div className="flex min-h-screen items-center justify-center px-4 py-8" style={{ background: 'linear-gradient(145deg, #eff6ff 0%, #f8fafc 48%, #e2e8f0 100%)' }}>
      <main className="w-full max-w-xl overflow-hidden rounded-2xl border border-slate-200 bg-white text-center shadow-2xl">
        <div className="bg-gradient-to-br from-blue-800 via-blue-700 to-blue-600 px-6 py-8 text-white">
          {props.logoUrl ? (
            <img
              src={props.logoUrl}
              alt="Anlux"
              className="mx-auto mb-5 h-14 max-w-[210px] rounded-lg bg-white object-contain px-3 py-2 shadow-lg"
            />
          ) : null}
          <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-white/20 text-3xl">
            <i className="fas fa-screwdriver-wrench" aria-hidden="true" />
          </div>
          <h1 className="mt-4 text-2xl font-extrabold sm:text-3xl">Sistema en mantenimiento</h1>
        </div>
        <div className="px-6 py-8 sm:px-8">
          <p className="whitespace-pre-line text-base leading-relaxed text-slate-600 sm:text-lg">{props.message}</p>
          <p className="mt-4 text-sm text-slate-500">
            Tu cuenta permanece segura. Intenta ingresar nuevamente cuando termine el mantenimiento.
          </p>
          {props.canDisable ? (
            <>
              <form method="POST" action={props.disableAction} className="mt-6">
                <input type="hidden" name="_token" value={props.csrf} />
                <input type="hidden" name="enabled" value="0" />
                <button
                  type="submit"
                  className="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-emerald-600 px-6 font-bold text-white shadow-lg hover:bg-emerald-700"
                >
                  <i className="fas fa-play" aria-hidden="true" />
                  Desactivar mantenimiento
                </button>
              </form>
              <a href={props.adminUrl} className="mt-4 inline-block text-sm font-bold text-blue-700 hover:underline">
                Volver al panel de administración
              </a>
            </>
          ) : (
            <form method="POST" action={props.logoutUrl} className="mt-6">
              <input type="hidden" name="_token" value={props.csrf} />
              <button
                type="submit"
                className="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-blue-700 px-6 font-bold text-white shadow-lg hover:bg-blue-800 sm:w-auto"
              >
                <i className="fas fa-arrow-left" aria-hidden="true" />
                Regresar al inicio de sesión
              </button>
            </form>
          )}
        </div>
      </main>
    </div>
  );
}
