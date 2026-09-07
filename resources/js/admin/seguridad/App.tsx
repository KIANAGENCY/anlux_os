import { readPageProps } from '../../shared/http';

type EventRow = {
  created_at?: string;
  event_label?: string;
  event_type?: string;
  event_explanation?: string;
  event_category?: string;
  severity?: string;
  usuario?: string;
  ip?: string;
  details_preview?: string;
};

type Props = {
  filterAction: string;
  clearUrl: string;
  secretActivo: boolean;
  totalOrdenes: number;
  actividad24h: number;
  alertas24h: number;
  bloqueos24h: number;
  severity: string;
  eventType: string;
  ip: string;
  eventTypes: string[];
  recent: EventRow[];
  suspiciousIps: Array<{ ip?: string; eventos?: number; ultimo_evento?: string }>;
};

export default function App() {
  const p = readPageProps<Props>();
  if (!p) {
    return <p className="p-4 text-red-700">No se cargaron los datos de seguridad.</p>;
  }

  return (
    <>
      <div className="mb-4 border-b-4 border-blue-700 pb-3">
        <h1 className="text-2xl font-bold text-blue-800 sm:text-3xl">
          <i className="fas fa-shield-alt mr-2" />
          Seguridad / Actividad
        </h1>
        <p className="mt-1 text-sm text-blue-900">Vista compacta con resumen y detalle de eventos de seguridad.</p>
      </div>

      <section className="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div className={`rounded-lg border p-3 ${p.secretActivo ? 'border-emerald-300 bg-emerald-50' : 'border-red-300 bg-red-50'}`}>
          <p className={`text-sm font-bold ${p.secretActivo ? 'text-emerald-800' : 'text-red-800'}`}>Secreto de encriptación</p>
          <p className={`mt-1 text-xl font-black ${p.secretActivo ? 'text-emerald-700' : 'text-red-700'}`}>{p.secretActivo ? 'Activo' : 'Falta'}</p>
        </div>
        <div className="rounded-lg border border-blue-200 bg-blue-50 p-3">
          <p className="text-sm font-bold text-blue-800">Órdenes</p>
          <p className="mt-1 text-xl font-black text-blue-700">{p.totalOrdenes}</p>
        </div>
        <div className="rounded-lg border border-slate-200 bg-slate-50 p-3">
          <p className="text-sm font-bold text-slate-800">Actividad 24h</p>
          <p className="mt-1 text-xl font-black text-slate-700">{p.actividad24h}</p>
        </div>
        <div className="rounded-lg border border-red-200 bg-red-50 p-3">
          <p className="text-sm font-bold text-red-800">Alertas/Bloqueos 24h</p>
          <p className="mt-1 text-xl font-black text-red-700">
            {p.alertas24h}
            {' '}
            /
            {' '}
            {p.bloqueos24h}
          </p>
        </div>
      </section>

      <section className="mb-4 rounded-lg border border-slate-200 bg-white p-4">
        <h2 className="mb-3 text-lg font-bold text-slate-900">Actividad de seguridad</h2>
        <form method="get" action={p.filterAction} className="mb-4 grid gap-3 rounded-lg border border-slate-100 bg-slate-50 p-3 sm:grid-cols-2 lg:grid-cols-4">
          <label className="block text-xs font-bold uppercase tracking-wide text-slate-600">
            Severidad
            <select name="severity" defaultValue={p.severity} className="mt-1 w-full rounded border border-slate-300 px-2 py-2 text-sm">
              <option value="">Todos</option>
              <option value="info">info</option>
              <option value="warning">warning</option>
              <option value="critical">critical</option>
            </select>
          </label>
          <label className="block text-xs font-bold uppercase tracking-wide text-slate-600">
            Tipo de evento
            <select name="event_type" defaultValue={p.eventType} className="mt-1 w-full rounded border border-slate-300 px-2 py-2 font-mono text-sm">
              <option value="">Todos</option>
              {p.eventTypes.map((t) => (
                <option key={t} value={t}>{t}</option>
              ))}
            </select>
          </label>
          <label className="block text-xs font-bold uppercase tracking-wide text-slate-600">
            IP
            <input type="text" name="ip" defaultValue={p.ip} placeholder="Igual o %comodín%" className="mt-1 w-full rounded border border-slate-300 px-2 py-2 font-mono text-sm" />
          </label>
          <div className="flex flex-wrap items-end gap-2">
            <button type="submit" className="rounded-lg bg-slate-800 px-4 py-2 text-sm font-bold text-white hover:bg-slate-900">Aplicar filtros</button>
            <a href={p.clearUrl} className="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Limpiar</a>
          </div>
        </form>

        <div className="overflow-x-auto rounded-lg border border-slate-100">
          <table className="w-full border-collapse text-sm" style={{ minWidth: 980 }}>
            <thead>
              <tr className="bg-slate-700 text-white">
                <th className="border p-3 text-left">Fecha</th>
                <th className="border p-3 text-left">Evento</th>
                <th className="border p-3 text-left">Categoría</th>
                <th className="border p-3 text-left">Severidad</th>
                <th className="border p-3 text-left">Usuario</th>
                <th className="border p-3 text-left">IP</th>
                <th className="border p-3 text-left">Detalle</th>
              </tr>
            </thead>
            <tbody>
              {p.recent.length === 0 ? (
                <tr><td colSpan={7} className="border p-4 text-center text-slate-600">Sin eventos.</td></tr>
              ) : p.recent.map((ev, i) => (
                <tr key={i} className="align-top hover:bg-slate-50">
                  <td className="whitespace-nowrap border p-3">{ev.created_at || ''}</td>
                  <td className="border p-3">
                    <p className="font-semibold text-slate-900">{ev.event_label || ev.event_type || ''}</p>
                    <p className="font-mono text-xs text-slate-500">{ev.event_type || ''}</p>
                    <p className="mt-1 text-xs text-slate-600">{ev.event_explanation || ''}</p>
                  </td>
                  <td className="border p-3">
                    <span className="rounded bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">{ev.event_category || 'otro'}</span>
                  </td>
                  <td className="border p-3">{ev.severity || ''}</td>
                  <td className="border p-3">{ev.usuario || '—'}</td>
                  <td className="border p-3 font-mono text-xs">{ev.ip || '—'}</td>
                  <td className="border p-3 text-xs text-slate-600">{ev.details_preview || '—'}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </section>

      {p.suspiciousIps.length > 0 ? (
        <section className="rounded-lg border border-amber-200 bg-amber-50 p-4">
          <h2 className="mb-3 text-lg font-bold text-amber-900">IPs sospechosas (24h)</h2>
          <ul className="space-y-2">
            {p.suspiciousIps.map((row, i) => (
              <li key={i} className="rounded border border-amber-200 bg-white px-3 py-2 text-sm">
                <strong className="font-mono">{row.ip || '—'}</strong>
                {' · '}
                {row.eventos || 0}
                {' eventos · último: '}
                {row.ultimo_evento || '—'}
              </li>
            ))}
          </ul>
        </section>
      ) : null}
    </>
  );
}
