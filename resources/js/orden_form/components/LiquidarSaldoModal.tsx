import { useMemo, useState } from 'react';
import { useAnluxDialog } from '../../shared/nav/ui';
import { aplicarLiquidacionEquipos, calcularSaldoEquipo } from '../lib/iva';
import type { AnticipoForm, EquipoForm, MaterialForm, TrabajoForm } from '../types';

export type LiquidarItem = { num: number; saldoConIva: number };

type Props = {
  open: boolean;
  equipos: EquipoForm[];
  trabajos: TrabajoForm[];
  materiales: MaterialForm[];
  anticipos: AnticipoForm[];
  abonoMap: Record<string, number>;
  saldoPendienteOrden: number;
  abonoSaldoActual: number;
  onClose: () => void;
  onApplied: (next: {
    abonoSaldo: number;
    abonoMap: Record<string, number>;
    saldoPagadoConfirmado: boolean;
  }) => void;
};

export default function LiquidarSaldoModal({
  open,
  equipos,
  trabajos,
  materiales,
  anticipos,
  abonoMap,
  saldoPendienteOrden,
  abonoSaldoActual,
  onClose,
  onApplied,
}: Props) {
  const { showAlert, showConfirm } = useAnluxDialog();
  const [selected, setSelected] = useState<Record<number, boolean>>({});

  const filas = useMemo(() => {
    return equipos.map((eq, idx) => {
      const num = idx + 1;
      return {
        num,
        marca: String(eq.marca || '').trim() || 'Sin marca',
        modelo: String(eq.modelo || '').trim() || 'Sin modelo',
        serie: String(eq.serie || '').trim(),
        saldo: calcularSaldoEquipo({
          numEquipo: num,
          trabajos,
          materiales,
          anticipos,
          abonoMap,
        }),
      };
    });
  }, [equipos, trabajos, materiales, anticipos, abonoMap]);

  if (!open) return null;

  const toggle = (num: number, checked: boolean) => {
    setSelected((prev) => ({ ...prev, [num]: checked }));
  };

  const onConfirm = async () => {
    const items: LiquidarItem[] = filas
      .filter((f) => selected[f.num])
      .map((f) => ({ num: f.num, saldoConIva: f.saldo }));
    if (!items.length) {
      await showAlert('Selecciona al menos un equipo', { title: 'Error', icon: 'error' });
      return;
    }
    const saldoSeleccionado = items.reduce((acc, it) => acc + it.saldoConIva, 0);
    const confirmar = await showConfirm(
      `¿Liquidar el saldo de $${saldoSeleccionado.toFixed(2)} de ${items.length} equipo(s) seleccionado(s)? El resto de la orden puede seguir con saldo.`,
      {
        title: 'Confirmar liquidación',
        confirmText: 'Sí, liquidar',
        cancelText: 'Cancelar',
        icon: 'warning',
      },
    );
    if (!confirmar) return;

    const result = aplicarLiquidacionEquipos({
      items,
      saldoPendienteOrden,
      abonoSaldoActual,
      abonoMap,
    });
    if (!result.ok) {
      await showAlert(result.message || 'No se pudo liquidar.', {
        title: result.message?.includes('No hay saldo') ? 'Saldo pendiente' : 'Sin saldo',
        icon: 'warning',
      });
      return;
    }

    const restante = Math.max(0, saldoPendienteOrden - result.aplicadoConIva);
    onApplied({
      abonoSaldo: result.abonoSaldo,
      abonoMap: result.abonoMap,
      saldoPagadoConfirmado: restante <= 0.009,
    });
    setSelected({});
    onClose();
    await showAlert(
      `Se liquidó $${result.aplicadoConIva.toFixed(2)} del equipo seleccionado.\nSaldo restante de la orden: $${restante.toFixed(2)}.`,
      { title: 'Pago aplicado', icon: 'success' },
    );
  };

  return (
    <div
      className="fixed inset-0 z-[21000] flex items-center justify-center bg-slate-950/80 p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby="liquidar-saldo-title"
    >
      <div className="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl bg-white shadow-xl">
        <div className="border-b border-slate-200 px-5 py-4">
          <h2 id="liquidar-saldo-title" className="text-lg font-bold text-slate-900">
            Liquidar Saldo por Equipo
          </h2>
          <p className="mt-1 text-sm text-slate-600">
            Selecciona los equipos a liquidar. El resto de la orden puede quedar con saldo.
          </p>
        </div>
        <div className="space-y-3 px-5 py-4">
          {!filas.length ? (
            <p className="text-slate-500">No hay equipos registrados en esta orden.</p>
          ) : (
            filas.map((eq) => {
              const serieTxt = eq.serie ? ` · Serie: ${eq.serie}` : '';
              return (
                <label
                  key={eq.num}
                  className="flex cursor-pointer flex-col gap-2 rounded-lg border-2 border-blue-200 bg-blue-50 p-3 sm:flex-row sm:items-center sm:justify-between"
                >
                  <span className="flex min-w-0 items-start gap-2">
                    <input
                      type="checkbox"
                      className="mt-1 h-4 w-4 rounded border-blue-600"
                      checked={!!selected[eq.num]}
                      onChange={(e) => toggle(eq.num, e.target.checked)}
                    />
                    <span className="text-sm text-blue-900">
                      <span className="font-bold">
                        Equipo
                        {' '}
                        {eq.num}
                        :
                      </span>
                      {' '}
                      {eq.marca}
                      {' '}
                      -
                      {' '}
                      {eq.modelo}
                      {serieTxt}
                    </span>
                  </span>
                  <span className="rounded-lg bg-red-600 px-3 py-2 text-center text-sm font-bold text-white sm:min-w-[10rem]">
                    SALDO: $
                    {eq.saldo.toFixed(2)}
                  </span>
                </label>
              );
            })
          )}
        </div>
        <div className="flex justify-end gap-2 border-t border-slate-200 px-5 py-4">
          <button
            type="button"
            className="rounded-lg border-2 border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            onClick={() => {
              setSelected({});
              onClose();
            }}
          >
            Cancelar
          </button>
          <button
            type="button"
            className="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700"
            onClick={() => void onConfirm()}
          >
            Liquidar seleccionados
          </button>
        </div>
      </div>
    </div>
  );
}
