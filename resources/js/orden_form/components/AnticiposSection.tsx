import type { AnticipoForm } from '../types';
import NetoIvaNumberInput from './NetoIvaNumberInput';

const cellInput =
  'w-full rounded border border-blue-300 px-2 py-1 text-sm focus:border-blue-600 focus:outline-none';

type Props = {
  anticipos: AnticipoForm[];
  numEquipos: number;
  readOnly: boolean;
  onChange: (next: AnticipoForm[]) => void;
};

export function emptyAnticipo(): AnticipoForm {
  return { folio: '', descripcion: '', monto: '', ticket: '', id_equipo: '' };
}

export default function AnticiposSection({ anticipos, numEquipos, readOnly, onChange }: Props) {
  const update = (idx: number, patch: Partial<AnticipoForm>) => {
    onChange(anticipos.map((a, i) => (i === idx ? { ...a, ...patch } : a)));
  };

  const add = () => onChange([...anticipos, emptyAnticipo()]);
  const remove = (idx: number) => {
    if (anticipos.length <= 1) {
      onChange([emptyAnticipo()]);
      return;
    }
    onChange(anticipos.filter((_, i) => i !== idx));
  };

  return (
    <section className="rounded-r-lg border-l-4 border-blue-500 bg-blue-50 p-4 sm:pl-6">
      <h2 className="mb-6 flex items-center text-xl font-bold text-blue-900 sm:text-2xl">
        <i className="fas fa-hand-holding-usd mr-3 text-blue-500" aria-hidden="true" />
        ANTICIPOS
      </h2>
      <div className="mb-4 overflow-x-auto rounded-lg border border-blue-100">
        <table className="w-full border-collapse text-sm" style={{ minWidth: 860 }}>
          <thead>
            <tr className="bg-blue-600 text-white">
              <th className="border p-3 text-left">FOLIO</th>
              <th className="border p-3 text-left">DESCRIPCIÓN</th>
              <th className="border p-3 text-left">MONTO (neto c/IVA)</th>
              <th className="border p-3 text-left">TICKET</th>
              <th className="border p-3 text-left">EQUIPO</th>
              <th className="border p-3 text-center">ACCIONES</th>
            </tr>
          </thead>
          <tbody>
            {anticipos.map((a, idx) => {
              const esSaldoPago = /^PAGO SALDO PENDIENTE$/i.test(String(a.ticket || '').trim());
              return (
              <tr key={idx} className="hover:bg-blue-100">
                <td className="border p-2">
                  <input
                    className={cellInput}
                    disabled={readOnly || esSaldoPago}
                    value={a.folio}
                    aria-label={`Folio de anticipo ${idx + 1}`}
                    data-anlux-field={`anticipo.${idx}.folio`}
                    onChange={(e) => update(idx, { folio: e.target.value.toUpperCase() })}
                    placeholder="Folio pedido"
                  />
                </td>
                <td className="border p-2">
                  <input
                    className={cellInput}
                    disabled={readOnly || esSaldoPago}
                    value={a.descripcion}
                    aria-label={`Descripción de anticipo ${idx + 1}`}
                    data-anlux-field={`anticipo.${idx}.descripcion`}
                    onChange={(e) => update(idx, { descripcion: e.target.value.toUpperCase() })}
                    placeholder="Descripción"
                  />
                </td>
                <td className="border p-2">
                  <div className="flex items-center gap-1">
                    <span>$</span>
                    <NetoIvaNumberInput
                      className={cellInput}
                      disabled={readOnly || esSaldoPago}
                      value={a.monto}
                      fieldId={`anticipo.${idx}.monto`}
                      onChangeSinIva={(monto) => update(idx, { monto })}
                      placeholder="Neto c/IVA"
                    />
                  </div>
                </td>
                <td className="border p-2">
                  <input
                    className={cellInput}
                    disabled={readOnly || esSaldoPago}
                    value={a.ticket}
                    aria-label={`Ticket de anticipo ${idx + 1}`}
                    onChange={(e) => update(idx, { ticket: e.target.value.toUpperCase() })}
                    placeholder="Ticket"
                    title={esSaldoPago ? 'Ticket de liquidación de saldo (bloqueado)' : undefined}
                  />
                </td>
                <td className="border p-2">
                  <select
                    className={cellInput}
                    disabled={readOnly}
                    value={a.id_equipo}
                    aria-label={`Equipo asignado a anticipo ${idx + 1}`}
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
                  {!readOnly && idx === anticipos.length - 1 ? (
                    <button
                      type="button"
                      className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-blue-300 bg-white text-blue-600 shadow-sm transition-colors duration-150 hover:bg-blue-50 hover:text-blue-800"
                      onClick={add}
                      title="Agregar anticipo"
                      aria-label="Agregar anticipo"
                    >
                      <i className="fas fa-plus" aria-hidden="true" />
                    </button>
                  ) : null}
                  {!readOnly ? (
                    <button
                      type="button"
                      className="ml-1.5 inline-flex h-9 w-9 items-center justify-center rounded-lg border border-red-300 bg-white text-red-600 shadow-sm transition-colors duration-150 hover:bg-red-50 hover:text-red-800"
                      onClick={() => remove(idx)}
                      title={`Quitar anticipo ${idx + 1}`}
                      aria-label={`Quitar anticipo ${idx + 1}`}
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
