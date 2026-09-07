import { Fragment, useCallback, useEffect, useRef, useState } from 'react';
import { abrirPdfSinCache, anluxUrl, parseJsonOrRedirect } from '../shared/http';
import { statusColor, statusLabel } from '../shared/status';
import { FilterModal } from '../shared/FilterModal';
import type { EstatusFiltro, OrdenListItem, SortOrdenes } from '../ordenes/types';

type EquipoEntregado = {
  indice: number;
  marca?: string;
  modelo?: string;
  serie?: string;
  receptor?: string;
  receptor_tipo?: string;
  fecha_entrega?: string | null;
  tecnico?: string;
};

function dateOnly(v: string | null | undefined): string {
  return v ? String(v).split(' ')[0] : '—';
}

export default function App() {
  const [searchInput, setSearchInput] = useState('');
  const [search, setSearch] = useState('');
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [estatus, setEstatus] = useState<EstatusFiltro>('todos');
  const [sort, setSort] = useState<SortOrdenes>('fecha');
  const [ordenes, setOrdenes] = useState<OrdenListItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [filterOpen, setFilterOpen] = useState(false);
  const [expanded, setExpanded] = useState<Record<string, boolean>>({});
  const [equipos, setEquipos] = useState<Record<string, EquipoEntregado[] | 'loading' | 'error'>>({});
  const lastPdfAt = useRef(0);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const qs = new URLSearchParams({
        search,
        startDate,
        endDate,
        estatus,
        perPage: '50',
        page: '1',
        sort,
      });
      const res = await fetch(anluxUrl(`/api/ordenes?${qs}`), {
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      });
      const data = await parseJsonOrRedirect(res) as {
        success: boolean;
        data?: OrdenListItem[] | Record<string, OrdenListItem>;
        message?: string;
      };
      if (!data.success) {
        setOrdenes([]);
        setError(data.message || 'No se pudo cargar el historial.');
        return;
      }
      setOrdenes(Array.isArray(data.data) ? data.data : Object.values(data.data || {}));
    } catch (err) {
      console.error(err);
      setError('Error de red al cargar el historial.');
      setOrdenes([]);
    } finally {
      setLoading(false);
    }
  }, [search, startDate, endDate, estatus, sort]);

  useEffect(() => {
    void load();
  }, [load]);

  const openPdf = (id: string | number, equipoIndice = 0) => {
    const now = Date.now();
    if (now - lastPdfAt.current < 300) return;
    lastPdfAt.current = now;
    const bust = `${Date.now()}_${Math.random().toString(36).slice(2, 8)}`;
    const eq = equipoIndice > 0 ? `&eq=${equipoIndice}` : '';
    void abrirPdfSinCache(`/pdf/orden/${id}?inline=1&refresh_pdf=1&nocache=1${eq}&_=${bust}`);
  };

  const toggleEquipos = async (id: string) => {
    const willOpen = !expanded[id];
    setExpanded((prev) => ({ ...prev, [id]: willOpen }));
    if (!willOpen || equipos[id]) return;
    setEquipos((prev) => ({ ...prev, [id]: 'loading' }));
    try {
      const res = await fetch(anluxUrl(`/api/ordenes/${encodeURIComponent(id)}/equipos-entregados`), {
        credentials: 'same-origin',
        cache: 'no-store',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      });
      const data = await parseJsonOrRedirect(res) as { success: boolean; data?: EquipoEntregado[]; message?: string };
      if (!data.success) throw new Error(data.message || 'Error');
      setEquipos((prev) => ({ ...prev, [id]: Array.isArray(data.data) ? data.data : [] }));
    } catch (err) {
      console.error(err);
      setEquipos((prev) => ({ ...prev, [id]: 'error' }));
    }
  };

  const applySearch = () => setSearch(searchInput.trim());

  return (
    <div className="anlux-page-card">
      <header className="anlux-page-header">
        <div>
          <p className="anlux-eyebrow">Anlux · Operación</p>
          <h1 className="anlux-page-title">Historial de órdenes</h1>
          <p className="anlux-page-description">Localiza servicios anteriores y consulta sus documentos de entrega.</p>
        </div>
        <a href={anluxUrl('/orden_servicio')} className="anlux-btn-primary">
          <i className="fas fa-plus" aria-hidden="true" />
          Nueva orden
        </a>
      </header>
      <div className="p-4 sm:p-5">
      <section className="anlux-panel-muted">
        <h2 className="mb-3 flex items-center text-base font-semibold text-slate-900">
          <i className="mr-3 text-blue-700 fas fa-search" />
          BÚSQUEDA Y FILTROS
        </h2>
        <div className="flex flex-col gap-3 md:flex-row">
          <input
            type="search"
            value={searchInput}
            onChange={(e) => setSearchInput(e.target.value)}
            onKeyDown={(e) => {
              if (e.key === 'Enter') {
                e.preventDefault();
                applySearch();
              }
            }}
            placeholder="Folio o nombre de cliente..."
            className="anlux-control flex-1"
          />
          <button type="button" onClick={applySearch} className="anlux-btn-primary">
            <i className="fas fa-search" />
          </button>
          <button type="button" onClick={() => setFilterOpen(true)} className="anlux-btn-secondary">
            <i className="fas fa-filter" />
          </button>
        </div>
      </section>

      <section className="mt-4">
        <h2 className="mb-3 flex items-center text-lg font-semibold text-slate-900">
          <i className="mr-3 text-blue-600 fas fa-list" />
          Historial de órdenes
        </h2>
        <p className="mb-3 text-sm text-blue-900">
          Abre el PDF completo o despliega
          {' '}
          <strong className="font-semibold text-emerald-700">Equipos entregados</strong>
          {' '}
          para consultar receptor, fecha y PDF individual.
        </p>
        <div className="anlux-table-wrap">
          <table className="anlux-table" style={{ minWidth: 720 }}>
            <thead>
              <tr>
                <th className="border p-3 text-left">FOLIO</th>
                <th className="border p-3 text-left">CLIENTE</th>
                <th className="border p-3 text-left">ENTRADA</th>
                <th className="border p-3 text-left">
                  <div className="flex flex-wrap items-center gap-2">
                    <span>ESTATUS</span>
                    <button
                      type="button"
                      onClick={() => setSort((s) => (s === 'estatus' ? 'fecha' : 'estatus'))}
                      className={
                        sort === 'estatus'
                          ? 'inline-flex min-h-8 min-w-8 items-center justify-center rounded-md border border-blue-600 bg-blue-600 px-2 py-1 text-xs font-semibold text-white shadow-sm hover:bg-blue-700'
                          : 'inline-flex min-h-8 min-w-8 items-center justify-center rounded-md border border-blue-300 bg-white px-2 py-1 text-xs font-semibold text-blue-800 shadow-sm hover:border-blue-500 hover:bg-blue-50'
                      }
                      title={sort === 'estatus' ? 'Orden: flujo. Clic para fecha.' : 'Orden: fecha. Clic para estatus.'}
                      aria-label="Ordenar por estatus o fecha"
                      aria-pressed={sort === 'estatus'}
                    >
                      <i className={`fas ${sort === 'estatus' ? 'fa-sort-amount-down' : 'fa-sort'}`} aria-hidden="true" />
                    </button>
                  </div>
                </th>
                <th className="border p-3 text-center">ACCIONES</th>
              </tr>
            </thead>
            <tbody>
              {loading && (
                <tr><td className="border p-3 text-center text-slate-600" colSpan={5}>Cargando órdenes…</td></tr>
              )}
              {!loading && error && (
                <tr><td className="border p-3 text-center text-red-700" colSpan={5}>{error}</td></tr>
              )}
              {!loading && !error && ordenes.length === 0 && (
                <tr><td className="border p-3 text-center" colSpan={5}>No se encontraron órdenes</td></tr>
              )}
              {!loading && !error && ordenes.map((orden) => {
                const id = String(orden.id_orden_c);
                const eqState = equipos[id];
                return (
                  <Fragment key={id}>
                    <tr className="border-b border-blue-100 hover:bg-blue-50">
                      <td className="border p-3 font-medium text-blue-900">{orden.folio || '—'}</td>
                      <td className="border p-3">{orden.nombre_cliente || '—'}</td>
                      <td className="border p-3">{dateOnly(orden.fecha_entrada)}</td>
                      <td className="border p-3 text-center">
                        <span className={`inline-flex items-center gap-2 rounded-full px-3 py-1 text-sm font-semibold ${statusColor(orden.estatus)}`}>
                          {orden.estatus_label || statusLabel(orden.estatus)}
                        </span>
                      </td>
                      <td className="border p-3 text-center">
                        <div className="flex flex-wrap justify-center gap-2">
                          <button type="button" onClick={() => openPdf(id)} className="inline-flex items-center rounded-lg bg-blue-600 px-3 py-1.5 text-sm font-bold text-white hover:bg-blue-700">
                            <i className="fas fa-external-link-alt mr-1" />
                            Abrir pestaña
                          </button>
                          <button
                            type="button"
                            onClick={() => void toggleEquipos(id)}
                            className="inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-bold text-white"
                            style={{ backgroundColor: '#059669' }}
                            aria-expanded={Boolean(expanded[id])}
                          >
                            <i className="fas fa-box-open mr-1" />
                            Equipos entregados
                          </button>
                        </div>
                      </td>
                    </tr>
                    {expanded[id] ? (
                      <tr className="bg-slate-50">
                        <td colSpan={5} className="border border-blue-100 p-4">
                          {eqState === 'loading' && <p className="text-sm text-slate-600">Cargando equipos entregados…</p>}
                          {eqState === 'error' && <p className="text-sm text-red-700">No se pudieron cargar los equipos entregados.</p>}
                          {Array.isArray(eqState) && eqState.length === 0 && (
                            <p className="text-sm text-slate-600">Esta orden no tiene equipos marcados como entregados.</p>
                          )}
                          {Array.isArray(eqState) && eqState.length > 0 && (
                            <div className="grid gap-3 md:grid-cols-2">
                              {eqState.map((equipo) => {
                                const tipo = equipo.receptor_tipo === 'tercero' ? 'Tercero' : 'Cliente titular';
                                const fecha = equipo.fecha_entrega
                                  ? new Date(String(equipo.fecha_entrega).replace(' ', 'T')).toLocaleString('es-MX')
                                  : 'Sin fecha';
                                const titulo = `${equipo.marca || ''} ${equipo.modelo || ''}`.trim() || `Equipo ${equipo.indice}`;
                                return (
                                  <article key={`${id}-${equipo.indice}`} className="rounded-lg border border-emerald-200 bg-white p-4 shadow-sm">
                                    <div className="mb-2 flex items-start justify-between gap-2">
                                      <strong className="text-blue-900">{titulo}</strong>
                                      <span className="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-800">Entregado</span>
                                    </div>
                                    <p className="text-sm text-slate-700">
                                      <strong>Serie:</strong>
                                      {' '}
                                      {equipo.serie || '—'}
                                    </p>
                                    <p className="text-sm text-slate-700">
                                      <strong>Recibió:</strong>
                                      {' '}
                                      {equipo.receptor || '—'}
                                      {' '}
                                      (
                                      {tipo}
                                      )
                                    </p>
                                    <p className="text-sm text-slate-700">
                                      <strong>Fecha:</strong>
                                      {' '}
                                      {fecha}
                                    </p>
                                    <p className="mb-3 text-sm text-slate-700">
                                      <strong>Técnico:</strong>
                                      {' '}
                                      {equipo.tecnico || '—'}
                                    </p>
                                    <button type="button" onClick={() => openPdf(id, Number(equipo.indice) || 0)} className="rounded-lg bg-blue-600 px-3 py-1.5 text-sm font-bold text-white hover:bg-blue-700">
                                      <i className="fas fa-file-pdf mr-1" />
                                      Ver PDF
                                    </button>
                                  </article>
                                );
                              })}
                            </div>
                          )}
                        </td>
                      </tr>
                    ) : null}
                  </Fragment>
                );
              })}
            </tbody>
          </table>
        </div>
      </section>

      <FilterModal
        open={filterOpen}
        startDate={startDate}
        endDate={endDate}
        estatus={estatus}
        onClose={() => setFilterOpen(false)}
        onApply={(next) => {
          setStartDate(next.startDate);
          setEndDate(next.endDate);
          setEstatus(next.estatus);
          setFilterOpen(false);
        }}
      />
      </div>
    </div>
  );
}
