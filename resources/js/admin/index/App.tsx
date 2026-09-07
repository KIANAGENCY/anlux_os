import { readPageProps } from '../../shared/http';

type LinkItem = { href: string; title: string; desc: string; icon: string; color: string };
type Props = {
  logoUrl: string;
  status: string | null;
  maintenance: { enabled: boolean; message: string };
  maintenanceAction: string;
  csrf: string;
  links: LinkItem[];
};

export default function App() {
  const props = readPageProps<Props>();
  if (!props) {
    return <p className="p-4 text-red-700">No se cargaron los datos del panel.</p>;
  }
  const m = props.maintenance;

  return (
    <div className="anlux-page-card">
      <header className="anlux-page-header">
        <div>
          <p className="anlux-eyebrow">Anlux · Administración</p>
          <h1 className="anlux-page-title">Panel de administrador</h1>
          <p className="anlux-page-description">Controla el mantenimiento y abre las áreas administrativas del sistema.</p>
        </div>
        <div className={`flex min-w-[280px] items-center gap-3 rounded-xl border px-4 py-3 ${m.enabled ? 'border-amber-200 bg-amber-50' : 'border-green-200 bg-green-50'}`} role="status">
          <span className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-white ${m.enabled ? 'bg-amber-500' : 'bg-green-600'}`}>
            <i className={`fas ${m.enabled ? 'fa-pause' : 'fa-circle-check'}`} aria-hidden="true" />
          </span>
          <div><p className="text-xs font-semibold text-slate-600">Estado actual</p><p className="text-sm font-bold text-slate-900">{m.enabled ? 'Mantenimiento activo' : 'Sistema disponible'}</p></div>
        </div>
      </header>

      <div className="p-4 sm:p-6">
        {props.status ? (
          <div className="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{props.status}</div>
        ) : null}

        <section className={`mt-5 grid gap-5 rounded-xl border p-5 shadow-sm md:grid-cols-[minmax(0,.85fr)_minmax(340px,1.15fr)] ${m.enabled ? 'border-amber-300 bg-amber-50' : 'border-slate-200 bg-slate-50'}`}>
          <div>
          <div className="flex items-center gap-3">
            <span className={`flex h-11 w-11 items-center justify-center rounded-xl ${m.enabled ? 'bg-amber-500 text-white' : 'bg-slate-200 text-slate-600'}`}>
              <i className="fas fa-screwdriver-wrench text-lg" />
            </span>
            <div>
              <h2 className="text-lg font-extrabold text-slate-900">Modo de mantenimiento</h2>
              <p className={`text-sm font-semibold ${m.enabled ? 'text-amber-800' : 'text-slate-500'}`}>
                {m.enabled ? 'ACTIVO: los usuarios no administradores están bloqueados.' : 'INACTIVO: el sistema está disponible.'}
              </p>
            </div>
          </div>
          <p className="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
            Actívalo antes de subir, sustituir o eliminar archivos. Los administradores podrán seguir usando este panel.
          </p>
          <div className="mt-4 rounded-lg border border-slate-300 bg-white px-4 py-3">
            <p className="text-sm font-semibold text-slate-900"><i className="fas fa-triangle-exclamation mr-2 text-amber-500" />Impacto al activarlo</p>
            <p className="mt-1 text-sm text-slate-600">Los usuarios no administradores quedarán bloqueados mientras el panel permanezca en mantenimiento.</p>
          </div>
          </div>
          <form method="POST" action={props.maintenanceAction} onSubmit={(event) => { if (!window.confirm(m.enabled ? '¿Confirmas que deseas desactivar el modo de mantenimiento?' : '¿Confirmas que deseas activar el modo de mantenimiento? Los usuarios no administradores quedarán bloqueados.')) event.preventDefault(); }}>
            <input type="hidden" name="_token" value={props.csrf} />
            <label htmlFor="maintenanceMessage" className="anlux-label">Mensaje para los usuarios</label>
            <textarea id="maintenanceMessage" name="message" rows={3} maxLength={500} defaultValue={m.message} className="anlux-control w-full py-3" />
            <p className="mt-1 text-xs text-slate-500">Este mensaje será visible durante el bloqueo operativo.</p>
            <div className="mt-4 flex flex-wrap justify-end gap-3">
              {m.enabled ? (
                <button type="submit" name="enabled" value="0" className="anlux-maintenance-button anlux-maintenance-button--disable">
                  <i className="fas fa-play" />
                  {' '}
                  Desactivar mantenimiento
                </button>
              ) : (
                <button type="submit" name="enabled" value="1" className="anlux-maintenance-button anlux-maintenance-button--enable">
                  <i className="fas fa-pause" />
                  {' '}
                  Activar mantenimiento
                </button>
              )}
            </div>
          </form>
        </section>

        <div className="mb-3 mt-6"><h2 className="text-lg font-semibold text-slate-900">Destinos administrativos</h2><p className="text-sm text-slate-600">Selecciona una sección para continuar.</p></div>
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          {props.links.map((link) => {
            return (
              <a key={link.href} href={link.href} className="group flex min-h-[96px] gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-blue-300 hover:bg-blue-50 focus:ring-2 focus:ring-blue-600">
                <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-700 transition group-hover:bg-blue-600 group-hover:text-white">
                  <i className={`fas ${link.icon} text-xl`} />
                </span>
                <span className="min-w-0">
                  <span className="block text-sm font-semibold text-slate-900 group-hover:text-blue-900">{link.title}</span>
                  <span className="mt-1 block text-sm leading-snug text-slate-600">{link.desc}</span>
                </span>
              </a>
            );
          })}
        </div>
      </div>
    </div>
  );
}
