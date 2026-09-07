import { computeOrdenTotales, type OrdenTotales } from '../lib/iva';
import type { AnticipoForm, MaterialForm, TrabajoForm } from '../types';

type Props = {
  trabajos: TrabajoForm[];
  materiales: MaterialForm[];
  anticipos: AnticipoForm[];
  abonoSaldo: number;
  readOnly: boolean;
  onAbonoSaldoChange: (value: number) => void;
  onLiquidarSaldo: () => void;
};

export function calcTotalesFromForm(
  trabajos: TrabajoForm[],
  materiales: MaterialForm[],
  anticipos: AnticipoForm[],
  abonoSaldo: number,
): OrdenTotales {
  return computeOrdenTotales({
    trabajosImportes: trabajos.map((t) => Number(t.importe) || 0),
    materiales: materiales.map((m) => ({
      cant: Number(m.cant) || 0,
      precio: Number(m.precio) || 0,
    })),
    anticiposMontos: anticipos.map((a) => Number(a.monto) || 0),
    abonoSaldo,
  });
}

export default function TotalesBar({
  trabajos,
  materiales,
  anticipos,
  abonoSaldo,
  readOnly,
  onLiquidarSaldo,
}: Props) {
  const totales = calcTotalesFromForm(trabajos, materiales, anticipos, abonoSaldo);
  const liquidado = Math.abs(totales.saldoPendiente) <= 0.009;

  return (
    <div className="mt-4 text-right text-sm text-blue-950 sm:text-base">
      <strong>
        SUBTOTAL DE TRABAJOS Y MATERIALES: $
        {totales.subtotalCombinado.toFixed(2)}
      </strong>
      <br />
      <div className="mt-1">
        {anticipos.map((a, i) => {
          const monto = Number(a.monto) || 0;
          if (!a.folio && !a.descripcion && !a.ticket && !a.monto) return null;
          return (
            <div key={i}>
              <strong>
                ANTICIPO
                {' '}
                {i + 1}
                : $
                {monto.toFixed(2)}
                {' '}
                (SIN IVA)
              </strong>
            </div>
          );
        })}
        {abonoSaldo > 0.009 ? (
          <div>
            <strong>
              ABONO SALDO: $
              {Number(abonoSaldo).toFixed(2)}
              {' '}
              (SIN IVA)
            </strong>
          </div>
        ) : null}
      </div>
      <strong>
        IVA (16%): $
        {totales.ivaTotal.toFixed(2)}
      </strong>
      <br />
      <strong className="mt-3 block">
        TOTAL: $
        {totales.total.toFixed(2)}
      </strong>
      <strong
        className="mt-3 inline-block rounded-lg px-4 py-3 text-lg text-white shadow-sm"
        style={{ backgroundColor: liquidado ? '#16a34a' : '#dc2626' }}
      >
        SALDO PENDIENTE: $
        {totales.saldoPendiente.toFixed(2)}
      </strong>
      {!readOnly && !liquidado ? (
        <div className="mt-3 flex justify-end">
          <button
            type="button"
            onClick={() => onLiquidarSaldo()}
            className="inline-flex items-center justify-center rounded-lg bg-red-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-red-700"
            title="Liquida el saldo pendiente por equipo"
          >
            <i className="fas fa-cash-register mr-2" />
            Liquidar Saldo
          </button>
        </div>
      ) : null}
    </div>
  );
}
