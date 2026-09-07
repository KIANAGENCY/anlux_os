import type { OrdenListItem } from '../types';
import { asBool, dateOnly, dateTimeShort, primerTecnicoOrden, secuenciaInvolucradosOrden } from '../helpers';
import { isEntregado, statusColor, statusLabel } from '../status';

type OrdersTableProps = {
  ordenes: OrdenListItem[];
  loading: boolean;
  error: string | null;
  onOpenOrden: (id: string) => void;
  onBlocked: (nombre: string) => void;
  onEditStatus: (orden: OrdenListItem) => void;
  onPdf: (id: string, folio: string) => void;
  onDownload: (id: string) => void;
};

export function OrdersTable({
  ordenes,
  loading,
  error,
  onOpenOrden,
  onBlocked,
  onEditStatus,
  onPdf,
  onDownload,
}: OrdersTableProps) {
  if (loading && ordenes.length === 0) {
    return (
      <tbody>
        <tr>
          <td className="border p-3 text-center" colSpan={10}>
            Cargando órdenes…
          </td>
        </tr>
      </tbody>
    );
  }

  if (error && ordenes.length === 0) {
    return (
      <tbody>
        <tr>
          <td className="border p-3 text-center text-red-700" colSpan={10}>
            {error}
          </td>
        </tr>
      </tbody>
    );
  }

  if (!ordenes.length) {
    return (
      <tbody>
        <tr>
          <td className="border p-3 text-center" colSpan={10}>
            No se encontraron órdenes
          </td>
        </tr>
      </tbody>
    );
  }

  return (
    <tbody>
      {ordenes.map((orden) => {
        const id = String(orden.id_orden_c);
        const lockActivo = asBool(orden.edit_lock_active) && !asBool(orden.edit_lock_is_mine);
        const lockNombre = String(orden.edit_lock_nombre || '').trim();
        const ordenEntregada = isEntregado(orden.estatus);
        const salidaActiva = asBool(orden.salida_temporal_activa);
        const fechaSalidaTemp = orden.fecha_salida_temporal
          ? String(orden.fecha_salida_temporal).split(' ')[0]
          : '';
        const huboSalidaTemp = salidaActiva || Boolean(fechaSalidaTemp);
        const rowClass = lockActivo
          ? 'bg-red-100 hover:bg-red-200 ring-1 ring-inset ring-red-300'
          : (salidaActiva
            ? 'bg-orange-50 hover:bg-orange-100 cursor-pointer'
            : 'cursor-pointer hover:bg-blue-100');
        const involucrados = secuenciaInvolucradosOrden(orden);
        const tituloLock = lockActivo
          ? `En edición por ${lockNombre || 'otro usuario'}`
          : (ordenEntregada ? 'Ver orden (solo lectura)' : 'Ver / completar orden');

        return (
          <tr
            key={id}
            className={rowClass}
            onClick={(e) => {
              const target = e.target as HTMLElement;
              if (target.closest('button') || target.closest('a')) return;
              if (lockActivo) {
                onBlocked(lockNombre || 'otro usuario');
                return;
              }
              onOpenOrden(id);
            }}
          >
            <td className="border p-3">{orden.folio || '—'}</td>
            <td className="border p-3">{orden.nombre_cliente || '—'}</td>
            <td className="border p-3">{dateOnly(orden.fecha_entrada)}</td>
            <td className="border p-3">{dateTimeShort(orden.fecha_terminada)}</td>
            <td className="border p-3">{dateOnly(orden.fecha_salida)}</td>
            <td className="border p-3 text-center">
              {salidaActiva ? (
                <span className="inline-flex flex-col items-center gap-0.5" title="Equipo fuera del taller (salida temporal)">
                  <span className="inline-flex items-center rounded-full bg-orange-600 px-2.5 py-1 text-[11px] font-extrabold uppercase tracking-wide text-white">
                    Se dio salida
                  </span>
                  {fechaSalidaTemp ? (
                    <span className="text-[11px] font-semibold text-orange-800">{fechaSalidaTemp}</span>
                  ) : null}
                </span>
              ) : fechaSalidaTemp ? (
                <span className="inline-flex flex-col items-center gap-0.5" title="Hubo salida temporal y el equipo ya regresó">
                  <span className="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-900">
                    Salida (regresó)
                  </span>
                  <span className="text-[11px] text-slate-600">{fechaSalidaTemp}</span>
                </span>
              ) : (
                '—'
              )}
            </td>
            <td className="min-w-[12rem] border p-3 text-sm text-blue-900">
              {primerTecnicoOrden(orden) || '—'}
            </td>
            <td className="min-w-[16rem] whitespace-normal border p-3 align-top text-sm leading-relaxed text-slate-700">
              {involucrados.length
                ? involucrados.map((nombre, idx) => (
                    <div key={`${id}-${idx}`}>
                      {idx + 1}
                      .
                      {' '}
                      {nombre}
                    </div>
                  ))
                : '—'}
            </td>
            <td className="border p-3 text-center">
              <div className="inline-flex flex-col items-center">
                <span className={`inline-flex items-center gap-2 rounded-full px-3 py-1 text-sm font-semibold ${statusColor(orden.estatus)}`}>
                  <span className="h-2 w-2 rounded-full bg-white/80" />
                  {orden.estatus_label || statusLabel(orden.estatus)}
                </span>
                {salidaActiva ? (
                  <div className="mt-1">
                    <span className="inline-flex items-center rounded-md bg-orange-600 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wide text-white">
                      Se dio salida
                    </span>
                  </div>
                ) : huboSalidaTemp ? (
                  <div className="mt-1">
                    <span className="inline-flex items-center rounded-md bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-900">
                      Hubo salida temp.
                    </span>
                  </div>
                ) : null}
              </div>
            </td>
            <td className="border p-3 text-center">
              <div className="flex flex-wrap items-center justify-center gap-2">
                {ordenEntregada ? (
                  <button
                    type="button"
                    className="inline-flex h-8 w-8 items-center justify-center rounded-full text-emerald-700 hover:bg-emerald-100"
                    title={tituloLock}
                    onClick={() => onOpenOrden(id)}
                  >
                    <i className="fas fa-eye" />
                  </button>
                ) : lockActivo ? (
                  <button
                    type="button"
                    className="inline-flex h-8 w-8 cursor-not-allowed items-center justify-center rounded-full text-red-400 opacity-80"
                    title={tituloLock}
                    disabled
                    onClick={() => onBlocked(lockNombre || 'otro usuario')}
                  >
                    <i className="fas fa-pencil-alt" />
                  </button>
                ) : (
                  <button
                    type="button"
                    className="inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-600 hover:bg-slate-100 hover:text-slate-900"
                    title={tituloLock}
                    onClick={() => onOpenOrden(id)}
                  >
                    <i className="fas fa-pencil-alt" />
                  </button>
                )}

                {ordenEntregada ? (
                  <button
                    type="button"
                    className="inline-flex h-8 w-8 cursor-not-allowed items-center justify-center rounded-full text-slate-300 opacity-60"
                    title="Orden entregada: no se puede editar estatus"
                    disabled
                  >
                    <i className="fas fa-check" />
                  </button>
                ) : (
                  <button
                    type="button"
                    className="inline-flex h-8 w-8 items-center justify-center rounded-full text-blue-600 hover:bg-blue-100 hover:text-blue-800"
                    title="Editar estatus"
                    onClick={() => onEditStatus(orden)}
                  >
                    <i className="fas fa-check" />
                  </button>
                )}

                <button
                  type="button"
                  className="inline-flex h-8 w-8 items-center justify-center rounded-full text-indigo-600 hover:bg-indigo-100 hover:text-indigo-800"
                  title="Ver PDF en pantalla (sin descargar)"
                  onClick={() => onPdf(id, String(orden.folio || ''))}
                >
                  <i className="fas fa-file-pdf" />
                </button>
                <button
                  type="button"
                  className="inline-flex h-8 w-8 items-center justify-center rounded-full text-green-600 hover:bg-green-100 hover:text-green-800"
                  title="Descargar PDF"
                  onClick={() => onDownload(id)}
                >
                  <i className="fas fa-download" />
                </button>
              </div>
            </td>
          </tr>
        );
      })}
    </tbody>
  );
}
