import { readPageProps, csrfToken } from '../../shared/http';

type Props = {
  anio: number;
  indexAction: string;
  syncAction: string;
  success: string | null;
  status: {
    next_num: number;
    max_usado: number;
    proximo_folio: string;
    huecos: string[];
  };
};

export default function App() {
  const p = readPageProps<Props>();
  if (!p) {
    return <p className="p-4 text-red-700">No se cargaron los datos de folios.</p>;
  }
  const { status } = p;

  return (
    <div>
      <header className="-mx-4 -mt-5 mb-5 border-b border-slate-200 bg-gradient-to-br from-blue-800 via-blue-700 to-blue-600 px-5 py-6 sm:-mx-6 sm:-mt-6 sm:px-8">
        <div className="text-white">
          <p className="text-xs font-semibold uppercase tracking-widest text-blue-200">Anlux · Administración</p>
          <h1 className="mt-1 text-2xl font-bold sm:text-3xl">Folios de órdenes</h1>
          <p className="mt-2 max-w-xl text-sm text-blue-100">
            Consulta huecos liberados y sincroniza el contador. La próxima orden nueva reutilizará el menor hueco libre.
          </p>
        </div>
      </header>

      <div>
        {p.success ? (
          <div className="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{p.success}</div>
        ) : null}

        <form method="get" action={p.indexAction} className="mt-6 flex flex-wrap items-end gap-3">
          <div>
            <label htmlFor="anio" className="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-600">Año</label>
            <input type="number" name="anio" id="anio" defaultValue={p.anio} min={2000} max={2100} className="w-28 rounded-lg border-2 border-slate-300 px-3 py-2 text-sm font-semibold" />
          </div>
          <button type="submit" className="rounded-lg bg-blue-700 px-4 py-2 text-sm font-bold text-white hover:bg-blue-800">Ver</button>
        </form>

        <div className="mt-6 grid gap-3 sm:grid-cols-2">
          <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p className="text-xs font-bold uppercase text-slate-500">Contador next_num</p>
            <p className="mt-1 text-2xl font-bold text-slate-900">{status.next_num}</p>
          </div>
          <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p className="text-xs font-bold uppercase text-slate-500">Máximo usado</p>
            <p className="mt-1 text-2xl font-bold text-slate-900">{status.max_usado}</p>
          </div>
          <div className="rounded-xl border border-orange-200 bg-orange-50 p-4 sm:col-span-2">
            <p className="text-xs font-bold uppercase text-orange-800">Próximo folio a asignar</p>
            <p className="mt-1 text-2xl font-bold text-orange-950">{status.proximo_folio}</p>
            <p className="mt-1 text-sm text-orange-900">
              Si hay huecos, se usa el menor (ej. OS-
              {p.anio}
              -003) sin renumerar las órdenes existentes.
            </p>
          </div>
        </div>

        <div className="mt-6">
          <h2 className="text-lg font-bold text-slate-900">
            Huecos libres (
            {status.huecos.length}
            )
          </h2>
          {status.huecos.length === 0 ? (
            <p className="mt-2 text-sm text-slate-600">No hay huecos en este año. La secuencia está continua.</p>
          ) : (
            <ul className="mt-3 flex flex-wrap gap-2">
              {status.huecos.map((h) => (
                <li key={h} className="rounded-full border border-amber-300 bg-amber-50 px-3 py-1 text-sm font-bold text-amber-950">{h}</li>
              ))}
            </ul>
          )}
        </div>

        <form
          method="post"
          action={p.syncAction}
          className="mt-8 border-t border-slate-200 pt-6"
          onSubmit={(e) => {
            if (!window.confirm(`¿Sincronizar el contador next_num con max(usados)+1 para ${p.anio}?`)) {
              e.preventDefault();
            }
          }}
        >
          <input type="hidden" name="_token" value={csrfToken()} />
          <input type="hidden" name="anio" value={p.anio} />
          <p className="mb-3 text-sm text-slate-600">
            Usa esto si el contador quedó desfasado tras borrar órdenes. No cambia folios existentes.
          </p>
          <button type="submit" className="rounded-lg bg-amber-600 px-4 py-2 text-sm font-bold text-white hover:bg-amber-700">
            Sincronizar contador
          </button>
        </form>
      </div>
    </div>
  );
}
