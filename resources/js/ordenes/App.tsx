import { useCallback, useEffect, useState } from 'react';
import { abrirPdfOrdenSinCache, anluxUrl, fetchOrdenes, updateOrdenEstatus } from './api';
import { asBool } from './helpers';
import { estatusRadioRank, statusToRadio } from './status';
import type { EstatusFiltro, EstatusRadio, OrdenListItem, SortOrdenes } from './types';
import { AlertModal } from './components/AlertModal';
import { EditStatusModal } from './components/EditStatusModal';
import { OrdersTable } from './components/OrdersTable';
import { IconCaretLeft, IconCaretRight, IconMagnifyingGlass, IconSort } from '../shared/icons';

const STATUS_CHIPS: { value: EstatusFiltro; label: string }[] = [
  { value: 'todos', label: 'Todos' },
  { value: 'rojo', label: 'Recepción' },
  { value: 'naranja', label: 'En proceso' },
  { value: 'amarillo', label: 'Terminado' },
  { value: 'verde', label: 'Entregado' },
];

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
  const [reloadToken, setReloadToken] = useState(0);
  const [alert, setAlert] = useState<{ title: string; message: string } | null>(null);
  const [edit, setEdit] = useState<{
    id: string;
    folio: string;
    selected: EstatusRadio;
    origen: EstatusRadio;
    salidaTemporalActiva: boolean;
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
          <p className="anlux-eyebrow">Cola de trabajo</p>
          <h1 className="anlux-page-title">Órdenes</h1>
          <p className="anlux-page-description">Busca, abre y continúa el servicio. El PDF y el estatus son acciones secundarias.</p>
        </div>
      </header>
      <div className="space-y-3 p-4">
        <div className="flex flex-col gap-2 md:flex-row">
          <label className="sr-only" htmlFor="ordenes-search">Buscar cliente o folio</label>
          <input
            id="ordenes-search"
            value={searchInput}
            onChange={(e) => setSearchInput(e.target.value)}
            onKeyDown={(e) => {
              if (e.key === 'Enter') {
                e.preventDefault();
                applySearch();
              }
            }}
            placeholder="Buscar cliente o folio"
            className="anlux-control flex-1"
          />
          <button type="button" onClick={applySearch} className="anlux-btn-primary">
            <span className="anlux-icon-box"><IconMagnifyingGlass size={20} /></span>
            Buscar
          </button>
        </div>

        <div className="flex flex-wrap gap-2" role="group" aria-label="Filtrar por estatus">
          {STATUS_CHIPS.map((chip) => (
            <button
              key={chip.value}
              type="button"
              className="anlux-chip"
              aria-pressed={estatus === chip.value}
              onClick={() => {
                setEstatus(chip.value);
                setPage(1);
                setReloadToken((n) => n + 1);
              }}
            >
              {chip.label}
            </button>
          ))}
          <button
            type="button"
            className="anlux-chip"
            aria-pressed={sort === 'estatus'}
            onClick={() => {
              setSort((s) => (s === 'estatus' ? 'fecha' : 'estatus'));
              setPage(1);
            }}
          >
            <span className="anlux-icon-box"><IconSort size={16} /></span>
            {sort === 'estatus' ? 'Orden: estatus' : 'Orden: fecha'}
          </button>
        </div>

        <div className="flex flex-wrap gap-2">
          <label className="flex min-h-11 items-center gap-2 text-sm text-slate-700">
            Desde
            <input
              type="date"
              value={startDate}
              onChange={(e) => {
                setStartDate(e.target.value);
                setPage(1);
                setReloadToken((n) => n + 1);
              }}
              className="anlux-control min-h-11"
            />
          </label>
          <label className="flex min-h-11 items-center gap-2 text-sm text-slate-700">
            Hasta
            <input
              type="date"
              value={endDate}
              onChange={(e) => {
                setEndDate(e.target.value);
                setPage(1);
                setReloadToken((n) => n + 1);
              }}
              className="anlux-control min-h-11"
            />
          </label>
        </div>

        <OrdersTable
          ordenes={ordenes}
          loading={loading}
          error={error}
          onOpenOrden={openOrden}
          onBlocked={(nombre) => {
            void showAlert(`Esta orden está en edición por ${nombre}. Espera a que termine.`, 'Orden en uso');
          }}
          onEditStatus={(orden) => {
            const origen = statusToRadio(orden.estatus);
            if (origen === 'verde') return;
            const selected = origen;
            setEdit({
              id: String(orden.id_orden_c),
              folio: String(orden.folio || ''),
              selected,
              origen,
              salidaTemporalActiva: asBool(orden.salida_temporal_activa),
            });
          }}
          onPdf={(id) => void abrirPdfOrdenSinCache(id, true)}
          onDownload={(id) => void abrirPdfOrdenSinCache(id, false)}
        />

        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <p className="text-sm text-slate-600">
            {loading && ordenes.length === 0
              ? 'Cargando órdenes…'
              : total === 0
                ? 'No hay órdenes para mostrar'
                : `Mostrando ${from}–${to} de ${total} órdenes`}
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
              className="anlux-control w-20"
            >
              <option value={10}>10</option>
              <option value={25}>25</option>
              <option value={50}>50</option>
            </select>
            <button
              type="button"
              disabled={page <= 1}
              onClick={() => setPage((p) => Math.max(1, p - 1))}
              className="anlux-btn-secondary"
            >
              <IconCaretLeft size={18} />
              Anterior
            </button>
            <span className="text-sm font-semibold text-slate-900">
              {page}
              {' / '}
              {totalPages}
            </span>
            <button
              type="button"
              disabled={page >= totalPages}
              onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
              className="anlux-btn-secondary"
            >
              Siguiente
              <IconCaretRight size={18} />
            </button>
          </div>
        </div>
      </div>

      <EditStatusModal
        open={Boolean(edit)}
        ordenId={edit?.id || ''}
        folio={edit?.folio || ''}
        selected={edit?.selected || 'rojo'}
        origen={edit?.origen || 'rojo'}
        salidaTemporalActiva={edit?.salidaTemporalActiva || false}
        saving={saving}
        onChange={(v) => setEdit((prev) => (prev ? { ...prev, selected: v } : prev))}
        onClose={() => setEdit(null)}
        onSave={() => {
          if (!edit) return;
          void (async () => {
            if (edit.salidaTemporalActiva && edit.selected === 'amarillo' && edit.selected !== edit.origen) {
              await showAlert(
                'No puedes pasar a Terminado mientras haya salida temporal activa. Registra el regreso del equipo en la orden.',
                'Salida temporal',
              );
              return;
            }
            if (estatusRadioRank(edit.selected) < estatusRadioRank(edit.origen)) {
              const msg =
                'Vas a retroceder el estatus de la orden (corrección de error). ¿Confirmas el cambio?';
              const ok =
                typeof window.anluxShowConfirm === 'function'
                  ? await window.anluxShowConfirm(msg, {
                      title: 'Confirmar corrección',
                      icon: 'warning',
                    })
                  : window.confirm(msg);
              if (!ok) return;
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
  );
}
