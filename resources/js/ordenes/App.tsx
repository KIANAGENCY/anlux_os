import { useCallback, useEffect, useState } from 'react';
import { abrirPdfOrdenSinCache, anluxUrl, fetchOrdenes, updateOrdenEstatus } from './api';
import { asBool } from './helpers';
import { statusToRadio } from './status';
import type { EstatusFiltro, EstatusRadio, OrdenListItem, SortOrdenes } from './types';
import { AlertModal } from './components/AlertModal';
import { EditStatusModal } from './components/EditStatusModal';
import { FilterModal } from './components/FilterModal';
import { OrdersTable } from './components/OrdersTable';

export default function App() {
  const [searchInput, setSearchInput] = useState('');
  const [search, setSearch] = useState('');
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [estatus, setEstatus] = useState<EstatusFiltro>('todos');
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(10);
  const [sort, setSort] = useState<SortOrdenes>('fecha');
  const [ordenes, setOrdenes] = useState<OrdenListItem[]>([]);
  const [total, setTotal] = useState(0);
  const [totalPages, setTotalPages] = useState(1);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [filterOpen, setFilterOpen] = useState(false);
  const [reloadToken, setReloadToken] = useState(0);
  const [alert, setAlert] = useState<{ title: string; message: string } | null>(null);
  const [edit, setEdit] = useState<{
    id: string;
    folio: string;
    selected: EstatusRadio;
    origen: EstatusRadio;
    firmasOk: boolean;
  } | null>(null);
  const [saving, setSaving] = useState(false);

  const showAlert = useCallback(async (message: string, title = 'Aviso') => {
    if (typeof window.anluxShowAlert === 'function') {
      await window.anluxShowAlert(message, { title, icon: 'warning' });
      return;
    }
    setAlert({ title, message });
  }, []);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const data = await fetchOrdenes({
        search: search.trim(),
        startDate,
        endDate,
        estatus,
        page,
        perPage,
        sort,
      });
      if (!data.success) {
        setOrdenes([]);
        setError(data.message || 'No se pudo cargar el listado.');
        return;
      }
      const lista = Array.isArray(data.data)
        ? data.data
        : Object.values(data.data || {});
      setOrdenes(lista);
      setTotal(Number(data.pagination?.total || 0));
      setTotalPages(Number(data.pagination?.totalPages || 1));
      setPage(Number(data.pagination?.page || page));
    } catch (err) {
      console.error(err);
      setOrdenes([]);
      setError('Error de red al cargar órdenes.');
    } finally {
      setLoading(false);
    }
  }, [search, startDate, endDate, estatus, page, perPage, sort, reloadToken]);

  useEffect(() => {
    void load();
  }, [load]);

  const applySearch = () => {
    setSearch(searchInput.trim());
    setPage(1);
    setReloadToken((n) => n + 1);
  };

  useEffect(() => {
    const timer = window.setInterval(() => {
      if (!document.hidden) {
        void load();
      }
    }, 30000);
    return () => window.clearInterval(timer);
  }, [load]);

  const openOrden = (id: string) => {
    window.location.href = anluxUrl(`/orden_servicio/${encodeURIComponent(id)}?ref=ordenes`);
  };

  const from = total === 0 ? 0 : (page - 1) * perPage + 1;
  const to = Math.min(page * perPage, total);

  return (
    <div className="anlux-page-card">
      <header className="anlux-page-header">
        <div>
          <p className="anlux-eyebrow">Anlux · Operación</p>
          <h1 className="anlux-page-title">Órdenes registradas</h1>
          <p className="anlux-page-description">Consulta, filtra y continúa el trabajo de las órdenes de servicio.</p>
        </div>
        <a href={anluxUrl('/orden_servicio')} className="anlux-btn-primary">
          <i className="fas fa-plus" aria-hidden="true" />
          Nueva orden de servicio
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
            value={searchInput}
            onChange={(e) => setSearchInput(e.target.value)}
            onKeyDown={(e) => {
              if (e.key === 'Enter') {
                e.preventDefault();
                applySearch();
              }
            }}
            placeholder="Buscar cliente o folio..."
            className="anlux-control flex-1"
          />
          <button
            type="button"
            onClick={applySearch}
            className="anlux-btn-primary md:w-auto"
          >
            <i className="fas fa-search" />
          </button>
          <button
            type="button"
            onClick={() => setFilterOpen(true)}
            className="anlux-btn-secondary md:w-auto"
          >
            <i className="fas fa-filter" />
          </button>
        </div>
      </section>

      <section className="mt-4">
        <h2 className="mb-3 flex items-center text-lg font-semibold text-slate-900">
          <i className="mr-3 text-blue-600 fas fa-list" />
          ÓRDENES DE SERVICIO
        </h2>
        <div className="anlux-table-wrap">
          <table className="anlux-table" style={{ width: '100%', minWidth: '100%', tableLayout: 'auto' }}>
            <thead>
              <tr>
                <th className="whitespace-nowrap border p-3 text-left">NO. ORDEN</th>
                <th className="min-w-[10rem] border p-3 text-left">CLIENTE</th>
                <th className="whitespace-nowrap border p-3 text-left">ENTRADA</th>
                <th className="whitespace-nowrap border p-3 text-left">TERMINADA</th>
                <th className="whitespace-nowrap border p-3 text-left">ENTREGA</th>
                <th className="min-w-[8rem] border p-3 text-center" title="Aviso si el equipo salió temporalmente del taller">
                  SALIDA TEMP.
                </th>
                <th className="min-w-[12rem] border p-3 text-left">TECNICO</th>
                <th className="min-w-[16rem] border p-3 text-left">INVOLUCRADOS</th>
                <th className="min-w-[9rem] border p-3 text-center">
                  <div className="flex flex-wrap items-center justify-center gap-2">
                    <span>ESTATUS</span>
                    <button
                      type="button"
                      onClick={() => {
                        setSort((s) => (s === 'estatus' ? 'fecha' : 'estatus'));
                        setPage(1);
                      }}
                      className={
                        sort === 'estatus'
                          ? 'inline-flex min-h-8 min-w-8 items-center justify-center rounded-md border border-blue-600 bg-blue-600 px-2 py-1 text-xs font-semibold text-white shadow-sm hover:bg-blue-700'
                          : 'inline-flex min-h-8 min-w-8 items-center justify-center rounded-md border border-blue-300 bg-white px-2 py-1 text-xs font-semibold text-blue-800 shadow-sm hover:border-blue-500 hover:bg-blue-50'
                      }
                      title={
                        sort === 'estatus'
                          ? 'Orden: flujo de estatus. Clic para ordenar por fecha.'
                          : 'Orden: fecha. Clic para ordenar por estatus.'
                      }
                      aria-label="Ordenar por estatus o fecha"
                      aria-pressed={sort === 'estatus'}
                    >
                      <i className={`fas ${sort === 'estatus' ? 'fa-sort-amount-down' : 'fa-sort'}`} aria-hidden="true" />
                    </button>
                  </div>
                </th>
                <th className="whitespace-nowrap border p-3 text-center">ACCIONES</th>
              </tr>
            </thead>
            <OrdersTable
              ordenes={ordenes}
              loading={loading}
              error={error}
              onOpenOrden={openOrden}
              onBlocked={(nombre) => {
                void showAlert(`Esta orden está en edición por ${nombre}. Espera a que termine.`, 'Orden en uso');
              }}
              onEditStatus={(orden) => {
                const radio = statusToRadio(orden.estatus);
                const selected = radio === 'verde' ? 'amarillo' : radio;
                setEdit({
                  id: String(orden.id_orden_c),
                  folio: String(orden.folio || ''),
                  selected,
                  origen: selected,
                  firmasOk: asBool(orden.firmas_recepcion_ok),
                });
              }}
              onPdf={(id) => void abrirPdfOrdenSinCache(id, true)}
              onDownload={(id) => void abrirPdfOrdenSinCache(id, false)}
            />
          </table>
        </div>

        <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <p className="text-sm text-slate-600">
            {loading && ordenes.length === 0
              ? 'Cargando órdenes...'
              : total === 0
                ? 'No hay ordenes para mostrar'
                : `Mostrando ${from}-${to} de ${total} ordenes`}
          </p>
          <div className="flex flex-wrap items-center gap-2">
            <label htmlFor="perPageReact" className="text-sm text-slate-600">Mostrar</label>
            <select
              id="perPageReact"
              value={perPage}
              onChange={(e) => {
                setPerPage(Number(e.target.value) || 10);
                setPage(1);
              }}
              className="rounded-lg border border-blue-200 px-3 py-2 text-sm"
            >
              <option value={10}>10</option>
              <option value={25}>25</option>
              <option value={50}>50</option>
            </select>
            <button
              type="button"
              disabled={page <= 1}
              onClick={() => setPage((p) => Math.max(1, p - 1))}
              className="rounded-lg bg-blue-100 px-3 py-2 text-blue-700 hover:bg-blue-200 disabled:cursor-not-allowed disabled:opacity-50"
            >
              Anterior
            </button>
            <span className="text-sm font-semibold text-blue-900">
              {page}
              {' '}
              /
              {' '}
              {totalPages}
            </span>
            <button
              type="button"
              disabled={page >= totalPages}
              onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
              className="rounded-lg bg-blue-100 px-3 py-2 text-blue-700 hover:bg-blue-200 disabled:cursor-not-allowed disabled:opacity-50"
            >
              Siguiente
            </button>
          </div>
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
          setPage(1);
          setReloadToken((n) => n + 1);
        }}
      />

      <EditStatusModal
        open={Boolean(edit)}
        ordenId={edit?.id || ''}
        folio={edit?.folio || ''}
        selected={edit?.selected || 'rojo'}
        origen={edit?.origen || 'rojo'}
        firmasRecepcionOk={edit?.firmasOk || false}
        saving={saving}
        onChange={(v) => setEdit((prev) => (prev ? { ...prev, selected: v } : prev))}
        onClose={() => setEdit(null)}
        onSave={() => {
          if (!edit) return;
          void (async () => {
            if (
              (edit.selected === 'naranja' || edit.selected === 'amarillo')
              && !edit.firmasOk
              && edit.selected !== edit.origen
            ) {
              await showAlert(
                'No se puede poner en En proceso ni en Terminado sin las firmas de Cliente y Técnico. Abre la orden de servicio, completa esa sección y guarda.',
                'Validación requerida',
              );
              return;
            }
            setSaving(true);
            try {
              const result = await updateOrdenEstatus(edit.id, edit.selected);
              if (!result.success) {
                await showAlert(result.message || 'No se pudo actualizar el estatus', 'No se pudo guardar');
                return;
              }
              setEdit(null);
              await load();
            } catch (err) {
              console.error(err);
              await showAlert('Error de red al guardar el estatus.', 'Error');
            } finally {
              setSaving(false);
            }
          })();
        }}
      />

      <AlertModal
        open={Boolean(alert)}
        title={alert?.title || 'Aviso'}
        message={alert?.message || ''}
        onClose={() => setAlert(null)}
      />
      </div>
    </div>
  );
}
