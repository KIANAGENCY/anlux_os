import { anluxRound2 } from '../lib/iva';
import type { MaterialForm } from '../types';
import NetoIvaNumberInput from './NetoIvaNumberInput';

const cellInput =
  'w-full rounded border border-blue-300 px-2 py-1 text-sm focus:border-blue-600 focus:outline-none';

type Props = {
  materiales: MaterialForm[];
  numEquipos: number;
  readOnly: boolean;
  onChange: (next: MaterialForm[]) => void;
};

export function emptyMaterial(): MaterialForm {
  return {
    vale: '',
    codigo: '',
    cant: '',
    descripcion: '',
    precio: '',
    ticket: '',
    id_equipo: '',
  };
}

export default function MaterialesSection({ materiales, numEquipos, readOnly, onChange }: Props) {
  const update = (idx: number, patch: Partial<MaterialForm>) => {
    onChange(materiales.map((m, i) => (i === idx ? { ...m, ...patch } : m)));
  };

  const add = () => onChange([...materiales, emptyMaterial()]);
  const remove = (idx: number) => {
    if (materiales.length <= 1) {
      onChange([emptyMaterial()]);
      return;
    }
    onChange(materiales.filter((_, i) => i !== idx));
  };

  return (
    <section className="rounded-r-lg border-l-4 border-blue-500 bg-blue-50 p-4 sm:pl-6">
      <h2 className="mb-6 flex items-center text-xl font-bold text-blue-900 sm:text-2xl">
        <i className="fas fa-boxes mr-3 text-blue-500" />
        MATERIALES / REFACCIONES
      </h2>
      <div className="mb-4 overflow-x-auto rounded-lg border border-blue-100">
        <table className="w-full border-collapse text-sm" style={{ minWidth: 1000 }}>
          <thead>
            <tr className="bg-blue-600 text-white">
              <th className="border p-3 text-left">VALE</th>
              <th className="border p-3 text-left">CODIGO</th>
              <th className="border p-3 text-left">CANT</th>
              <th className="border p-3 text-left">DESCRIPCION</th>
              <th className="border p-3 text-left">PRECIO (neto c/IVA)</th>
              <th className="border p-3 text-left">IMPORTE</th>
              <th className="border p-3 text-left">TICKET</th>
              <th className="border p-3 text-left">EQUIPO</th>
              <th className="border p-3 text-center"> </th>
            </tr>
          </thead>
          <tbody>
            {materiales.map((m, idx) => {
              const importe = anluxRound2((Number(m.cant) || 0) * (Number(m.precio) || 0));
              return (
                <tr key={idx} className="hover:bg-blue-100">
                  <td className="border p-2">
                    <input className={cellInput} disabled={readOnly} value={m.vale} data-anlux-field={`material.${idx}.vale`} onChange={(e) => update(idx, { vale: e.target.value.toUpperCase() })} placeholder="Vale" />
                  </td>
                  <td className="border p-2">
                    <input className={cellInput} disabled={readOnly} value={m.codigo} onChange={(e) => update(idx, { codigo: e.target.value.toUpperCase() })} placeholder="Codigo" />
                  </td>
                  <td className="border p-2">
                    <input type="number" min="0" className={cellInput} disabled={readOnly} value={m.cant} data-anlux-field={`material.${idx}.cant`} onChange={(e) => update(idx, { cant: e.target.value })} placeholder="Cant" />
                  </td>
                  <td className="border p-2">
                    <input className={cellInput} disabled={readOnly} value={m.descripcion} onChange={(e) => update(idx, { descripcion: e.target.value.toUpperCase() })} placeholder="Descripcion" />
                  </td>
                  <td className="border p-2">
                    <div className="flex items-center gap-1">
                      <span>$</span>
                      <NetoIvaNumberInput
                        className={cellInput}
                        disabled={readOnly}
                        value={m.precio}
                        fieldId={`material.${idx}.precio`}
                        onChangeSinIva={(precio) => update(idx, { precio })}
                        placeholder="Neto c/IVA"
                      />
                    </div>
                  </td>
                  <td className="border p-2 text-sm font-semibold text-blue-900">
                    $
                    {importe.toFixed(2)}
                  </td>
                  <td className="border p-2">
                    <input className={cellInput} disabled={readOnly} value={m.ticket} onChange={(e) => update(idx, { ticket: e.target.value.toUpperCase() })} placeholder="Ticket" />
                  </td>
                  <td className="border p-2">
                    <select className={cellInput} disabled={readOnly} value={m.id_equipo} onChange={(e) => update(idx, { id_equipo: e.target.value })}>
                      <option value="">-</option>
                      {Array.from({ length: Math.max(1, numEquipos) }, (_, i) => (
                        <option key={i + 1} value={String(i + 1)}>
                          {i + 1}
                        </option>
                      ))}
                    </select>
                  </td>
                  <td className="border p-2 text-center">
                    {!readOnly && idx === materiales.length - 1 ? (
                      <button type="button" className="font-bold text-blue-600 hover:text-blue-800" onClick={add}>
                        <i className="fas fa-plus" />
                      </button>
                    ) : null}
                    {!readOnly ? (
                      <button type="button" className="ml-2 font-bold text-red-600 hover:text-red-800" onClick={() => remove(idx)}>
                        <i className="fas fa-trash" />
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
