import type { EquipoForm } from '../types';
import { useAnluxDialog } from '../../shared/nav/ui';

const cellInput =
  'w-full rounded border border-blue-300 px-2 py-1 text-sm focus:border-blue-600 focus:outline-none';

type Props = {
  equipos: EquipoForm[];
  tiposServicio: string[];
  readOnly: boolean;
  showAcciones: boolean;
  onChange: (next: EquipoForm[]) => void;
  onEntregarEquipo?: (indice1Based: number) => void;
};

function tipoServicioKey(valor: string): string {
  const key = String(valor || '')
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/^\d+[\.\)]\s*/, '')
    .trim();
  if (key.includes('manten')) return 'mantenimiento';
  if (key.includes('repar')) return 'reparacion';
  if (key.includes('instal')) return 'instalacion';
  if (key.includes('garant')) return 'garantia';
  if (key.includes('revisi') || key.includes('revision')) return 'revision';
  return key;
}

function resolveTipoServicioOptions(tipos: string[], valorGuardado: string): string[] {
  const valor = String(valorGuardado || '').trim();
  if (!valor) return tipos;
  if (tipos.includes(valor)) return tipos;
  const key = tipoServicioKey(valor);
  const fuzzy = tipos.find((t) => tipoServicioKey(t) === key);
  if (fuzzy) return tipos;
  return [...tipos, valor];
}

export function emptyEquipo(): EquipoForm {
  return {
    marca: '',
    modelo: '',
    serie: '',
    descripcionFalla: '',
    tipoServicio: '',
    acciones: 0,
  };
}

function resolveTipoValue(tipos: string[], valorGuardado: string): string {
  const valor = String(valorGuardado || '').trim();
  if (!valor) return '';
  if (tipos.includes(valor)) return valor;
  const key = tipoServicioKey(valor);
  const fuzzy = tipos.find((t) => tipoServicioKey(t) === key);
  return fuzzy || valor;
}

function estatusInfo(acciones: number): { text: string; className: string } {
  const a = Math.max(0, Number(acciones) || 0);
  if (a >= 2) {
    return { text: 'Entregado', className: 'bg-emerald-600 text-white' };
  }
  if (a >= 1) {
    return { text: 'Terminado', className: 'bg-amber-500 text-gray-900' };
  }
  return { text: 'Pendiente', className: 'bg-slate-500 text-white' };
}

export default function EquiposSection({
  equipos,
  tiposServicio,
  readOnly,
  showAcciones,
  onChange,
  onEntregarEquipo,
}: Props) {
  const { showAlert } = useAnluxDialog();
  const update = (idx: number, patch: Partial<EquipoForm>) => {
    onChange(equipos.map((eq, i) => (i === idx ? { ...eq, ...patch } : eq)));
  };

  const add = () => onChange([...equipos, emptyEquipo()]);

  const remove = (idx: number) => {
    void (async () => {
      if (equipos.length <= 1) {
        await showAlert('Debe haber al menos un equipo.', { title: 'Equipos', icon: 'warning' });
        return;
      }
      onChange(equipos.filter((_, i) => i !== idx));
    })();
  };

  return (
    <section className="rounded-r-lg border-l-4 border-blue-500 bg-blue-50 p-4 sm:pl-6">
      <h2 className="mb-6 flex items-center text-xl font-bold text-blue-900 sm:text-2xl">
        <i className="fas fa-list-ul mr-3 text-blue-500" />
        DESCRIPCION DE EQUIPOS
      </h2>

      {showAcciones ? (
        <div className="mb-4 rounded-lg border border-blue-200 bg-white px-4 py-3 text-sm text-blue-900">
          <i className="fas fa-truck mr-2 text-blue-600" aria-hidden="true" />
          Gestiona cada equipo por separado con el bot&oacute;n de su fila: primero <strong>Terminado</strong> y despu&eacute;s <strong>Entregado</strong> con firmas.
        </div>
      ) : null}

      <div className="mb-4 overflow-x-auto rounded-lg border border-blue-100">
        <table className="w-full border-collapse text-sm" style={{ minWidth: showAcciones ? 1080 : 980 }}>
          <thead>
            <tr className="bg-blue-600 text-white">
              <th className="border p-3 text-left">ID</th>
              <th className="border p-3 text-left">MARCA</th>
              <th className="border p-3 text-left">MODELO O DESCRIPCION DEL EQUIPO</th>
              <th className="border p-3 text-left">N.O SERIE</th>
              <th className="border p-3 text-left">DESCRIPCION DE FALLA</th>
              <th className="border p-3 text-left">TIPO DE SERVICIO</th>
              {showAcciones ? <th className="border p-3 text-center">ESTATUS</th> : null}
              <th className="border p-3 text-center"> </th>
            </tr>
          </thead>
          <tbody>
            {equipos.map((eq, idx) => {
              const info = estatusInfo(eq.acciones);
              const tipoOpts = resolveTipoServicioOptions(tiposServicio, eq.tipoServicio);
              const tipoValue = resolveTipoValue(tipoOpts, eq.tipoServicio);
              return (
                <tr key={idx} className="equipo-row hover:bg-blue-100">
                  <td className="border p-2 text-center font-semibold text-blue-900">{idx + 1}</td>
                  <td className="border p-2">
                    <input
                      className={cellInput}
                      disabled={readOnly}
                      value={eq.marca}
                      data-anlux-field={`equipo.${idx}.marca`}
                      onChange={(e) => update(idx, { marca: e.target.value.toUpperCase() })}
                      placeholder="Marca"
                    />
                  </td>
                  <td className="border p-2">
                    <input
                      className={cellInput}
                      disabled={readOnly}
                      value={eq.modelo}
                      data-anlux-field={`equipo.${idx}.modelo`}
                      onChange={(e) => update(idx, { modelo: e.target.value.toUpperCase() })}
                      placeholder="Modelo"
                    />
                  </td>
                  <td className="border p-2">
                    <input
                      className={cellInput}
                      disabled={readOnly}
                      value={eq.serie}
                      data-anlux-field={`equipo.${idx}.serie`}
                      onChange={(e) => update(idx, { serie: e.target.value.toUpperCase() })}
                      placeholder="Serie"
                    />
                  </td>
                  <td className="border p-2">
                    <input
                      className={cellInput}
                      disabled={readOnly}
                      value={eq.descripcionFalla}
                      data-anlux-field={`equipo.${idx}.descripcionFalla`}
                      onChange={(e) => update(idx, { descripcionFalla: e.target.value.toUpperCase() })}
                      placeholder="Descripcion de falla"
                    />
                  </td>
                  <td className="border p-2">
                    <select
                      className={cellInput}
                      disabled={readOnly}
                      value={tipoValue}
                      data-anlux-field={`equipo.${idx}.tipoServicio`}
                      onChange={(e) => update(idx, { tipoServicio: e.target.value })}
                    >
                      <option value="">-</option>
                      {tipoOpts.map((t) => (
                        <option key={t} value={t}>
                          {t}
                        </option>
                      ))}
                    </select>
                  </td>
                  {showAcciones ? (
                    <td className="border p-2 text-center">
                      <span className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold ${info.className}`}>
                        {info.text}
                      </span>
                    </td>
                  ) : null}
                  <td className="border p-2 text-center whitespace-nowrap">
                    {showAcciones && !readOnly ? (
                      <button
                        type="button"
                        disabled={Number(eq.acciones) >= 2}
                        className={`mr-2 inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border px-3 py-2 text-xs font-bold shadow-sm transition ${
                          Number(eq.acciones) >= 2
                            ? 'cursor-not-allowed border-emerald-200 bg-emerald-50 text-emerald-700'
                            : Number(eq.acciones) === 1
                              ? 'border-amber-300 bg-amber-50 text-amber-800 hover:bg-amber-100'
                              : 'border-blue-300 bg-white text-blue-700 hover:bg-blue-50'
                        }`}
                        title={
                          Number(eq.acciones) >= 2
                            ? 'Equipo ya entregado'
                            : Number(eq.acciones) === 1
                              ? 'Equipo terminado — listo para entregar'
                              : 'Terminar y entregar este equipo'
                        }
                        onClick={() => onEntregarEquipo?.(idx + 1)}
                      >
                        <i className={`fas ${Number(eq.acciones) >= 2 ? 'fa-check-circle' : 'fa-truck'}`} />
                        {Number(eq.acciones) >= 2
                          ? 'Entregado'
                          : Number(eq.acciones) === 1
                            ? 'Entregar equipo'
                            : 'Gestionar equipo'}
                      </button>
                    ) : null}
                    {!readOnly && idx === equipos.length - 1 ? (
                      <button type="button" className="font-bold text-blue-600 hover:text-blue-800" onClick={add} title="Agregar equipo">
                        <i className="fas fa-plus" />
                      </button>
                    ) : null}
                    {!readOnly && equipos.length > 1 ? (
                      <button
                        type="button"
                        className="ml-2 font-bold text-red-600 hover:text-red-800"
                        onClick={() => remove(idx)}
                        title="Quitar equipo"
                      >
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
