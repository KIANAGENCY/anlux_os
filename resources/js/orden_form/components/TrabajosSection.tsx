import type { ServicioSersop, TrabajoForm } from '../types';

const cellInput =
  'w-full rounded border border-blue-300 px-2 py-1 text-sm focus:border-blue-600 focus:outline-none';

type Props = {
  trabajos: TrabajoForm[];
  serviciosSersop: ServicioSersop[];
  numEquipos: number;
  readOnly: boolean;
  onChange: (next: TrabajoForm[]) => void;
};

export function emptyTrabajo(): TrabajoForm {
  return { clave: '', descripcion: '', importe: '', ticket: '', id_equipo: '' };
}

/** Etiqueta visible en el select de clave SERSOP (legacy orden_servicio.js). */
function formatSersopOptionLabel(servicio: ServicioSersop): string {
  const clave = String(servicio.clave || '').trim();
  const descripcion = String(servicio.descripcion || '').trim();
  const precio = Number(servicio.precio || 0).toFixed(2);
  return `${clave} - ${descripcion} ($${precio})`;
}

function savedClaveOptionLabel(trabajo: TrabajoForm): string {
  const clave = String(trabajo.clave || '').trim();
  const descripcion = String(trabajo.descripcion || '').trim();
  const importe = String(trabajo.importe || '').trim();
  if (descripcion || importe) {
    const precio = Number.parseFloat(importe || '0');
    const precioFmt = Number.isFinite(precio) ? precio.toFixed(2) : '0.00';
    return `${clave} - ${descripcion} ($${precioFmt})`;
  }
  return `${clave} (guardada)`;
}

export default function TrabajosSection({
  trabajos,
  serviciosSersop,
  numEquipos,
  readOnly,
  onChange,
}: Props) {
  const update = (idx: number, patch: Partial<TrabajoForm>) => {
    onChange(trabajos.map((t, i) => (i === idx ? { ...t, ...patch } : t)));
  };

  const onSelectClave = (idx: number, clave: string) => {
    const claveUp = String(clave || '').trim().toUpperCase();
    const svc = serviciosSersop.find((s) => String(s.clave || '').trim().toUpperCase() === claveUp);
    const editable =
      (svc ? String(svc.editable) === '1' || svc.editable === true : false) || claveUp === 'SERSOPSA';
    update(idx, {
      clave,
      descripcion: editable ? '' : (svc?.descripcion || ''),
      importe: editable ? '' : (svc ? String(Number(svc.precio || 0).toFixed(2)) : ''),
    });
  };

  const add = () => onChange([...trabajos, emptyTrabajo()]);
  const remove = (idx: number) => {
    if (trabajos.length <= 1) {
      onChange([emptyTrabajo()]);
      return;
    }
    onChange(trabajos.filter((_, i) => i !== idx));
  };

  return (
    <section className="rounded-r-lg border-l-4 border-blue-500 bg-blue-50 p-4 sm:pl-6">
      <h2 className="mb-6 flex items-center text-xl font-bold text-blue-900 sm:text-2xl">
        <i className="fas fa-tools mr-3 text-blue-500" aria-hidden="true" />
        TRABAJOS REALIZADOS POR EL TÉCNICO (TIEMPO TÉCNICO Y MANO DE OBRA)
      </h2>
      <div className="mb-4 overflow-x-auto rounded-lg border border-blue-100">
        <table className="w-full border-collapse text-sm" style={{ minWidth: 900 }}>
          <thead>
            <tr className="bg-blue-600 text-white">
              <th className="border p-3 text-left">CLAVE</th>
              <th className="border p-3 text-left">DESCRIPCIÓN</th>
              <th className="border p-3 text-left">PRECIO SIN IVA</th>
              <th className="border p-3 text-left">TICKET/FACTURA</th>
              <th className="border p-3 text-left">EQUIPO</th>
              <th className="border p-3 text-center">ACCIONES</th>
            </tr>
          </thead>
          <tbody>
            {trabajos.map((t, idx) => {
              const svc = serviciosSersop.find((s) => s.clave === t.clave);
              const claveUp = String(t.clave || '').trim().toUpperCase();
              const editable =
                (svc ? String(svc.editable) === '1' || svc.editable === true : !t.clave)
                || claveUp === 'SERSOPSA';
              return (
                <tr key={idx} className="hover:bg-blue-100">
                  <td className="border p-2">
                    <select
                      className={`${cellInput} max-w-none`}
                      style={{ minWidth: 'min(100%, 22rem)' }}
                      disabled={readOnly}
                      value={t.clave}
                      aria-label={`Clave de trabajo ${idx + 1}`}
                      data-anlux-field={`trabajo.${idx}.clave`}
                      onChange={(e) => onSelectClave(idx, e.target.value)}
                    >
                      <option value="">Clave...</option>
                      {serviciosSersop.map((s) => (
                        <option key={s.clave} value={s.clave}>
                          {formatSersopOptionLabel(s)}
                        </option>
                      ))}
                      {t.clave &&
                      !serviciosSersop.some((s) => String(s.clave || '').trim().toUpperCase() === claveUp) ? (
                        <option value={t.clave}>{savedClaveOptionLabel(t)}</option>
                      ) : null}
                    </select>
                  </td>
                  <td className="border p-2">
                    <input
                      className={cellInput}
                      disabled={readOnly || !editable}
                      value={t.descripcion}
                      aria-label={`Descripción de trabajo ${idx + 1}`}
                      data-anlux-field={`trabajo.${idx}.descripcion`}
                      onChange={(e) => update(idx, { descripcion: e.target.value.toUpperCase() })}
                      placeholder={editable ? 'Descripción del servicio extra' : 'Descripción del trabajo'}
                      title={editable ? 'SERVICIO EXTRA' : 'Este campo se toma del catálogo SERSOP.'}
                    />
                  </td>
                  <td className="border p-2">
                    <div className="flex items-center gap-1">
                      <span className="text-blue-800">$</span>
                      <input
                        type="number"
                        step="0.01"
                        className={`${cellInput} anlux-no-spin`}
                        disabled={readOnly || (!editable && !!t.clave)}
                        value={t.importe}
                        aria-label={`Precio sin IVA de trabajo ${idx + 1}`}
                        data-anlux-field={`trabajo.${idx}.importe`}
                        onChange={(e) => update(idx, { importe: e.target.value })}
                        placeholder={editable ? 'PRECIO SIN IVA (SERVICIO EXTRA)' : 'PRECIO SIN IVA'}
                      />
                    </div>
                  </td>
                  <td className="border p-2">
                    <input
                      className={cellInput}
                      disabled={readOnly}
                      value={t.ticket}
                      aria-label={`Ticket o factura de trabajo ${idx + 1}`}
                      onChange={(e) => update(idx, { ticket: e.target.value.toUpperCase() })}
                      placeholder="Ticket, factura o folio"
                    />
                  </td>
                  <td className="border p-2">
                    <select
                      className={cellInput}
                      disabled={readOnly}
                      value={t.id_equipo}
                      aria-label={`Equipo asignado a trabajo ${idx + 1}`}
                      onChange={(e) => update(idx, { id_equipo: e.target.value })}
                    >
                      <option value="">Equipo...</option>
                      {Array.from({ length: Math.max(1, numEquipos) }, (_, i) => (
                        <option key={i + 1} value={String(i + 1)}>
                          {i + 1}
                        </option>
                      ))}
                    </select>
                  </td>
                  <td className="border p-2 text-center whitespace-nowrap">
                    {!readOnly && idx === trabajos.length - 1 ? (
                      <button
                        type="button"
                        className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-blue-300 bg-white text-blue-600 shadow-sm transition-colors duration-150 hover:bg-blue-50 hover:text-blue-800"
                        onClick={add}
                        title="Agregar trabajo"
                        aria-label="Agregar trabajo"
                      >
                        <i className="fas fa-plus" aria-hidden="true" />
                      </button>
                    ) : null}
                    {!readOnly ? (
                      <button
                        type="button"
                        className="ml-1.5 inline-flex h-9 w-9 items-center justify-center rounded-lg border border-red-300 bg-white text-red-600 shadow-sm transition-colors duration-150 hover:bg-red-50 hover:text-red-800"
                        onClick={() => remove(idx)}
                        title={`Quitar trabajo ${idx + 1}`}
                        aria-label={`Quitar trabajo ${idx + 1}`}
                      >
                        <i className="fas fa-trash" aria-hidden="true" />
                      </button>
                    ) : null}
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>
    </section>
  );
}
