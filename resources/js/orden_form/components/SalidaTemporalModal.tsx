import { useEffect, useRef, useState } from 'react';
import { useAnluxDialog } from '../../shared/nav/ui';
import SignaturePad from './SignaturePad';
import type { EquipoForm, SalidaTemporalPayload, SignaturePadHandle } from '../types';

type Props = {
  open: boolean;
  busy?: boolean;
  equipos: EquipoForm[];
  onCancel: () => void;
  onConfirm: (payload: SalidaTemporalPayload) => void;
};

export default function SalidaTemporalModal({ open, busy = false, equipos, onCancel, onConfirm }: Props) {
  const { showAlert } = useAnluxDialog();
  const [motivo, setMotivo] = useState('');
  const [idEquipo, setIdEquipo] = useState(0);
  const firmaClienteRef = useRef<SignaturePadHandle | null>(null);
  const firmaTecnicoRef = useRef<SignaturePadHandle | null>(null);

  useEffect(() => {
    if (!open) return;
    setMotivo('');
    setIdEquipo(equipos.length === 1 ? Number(equipos[0]?.id_equipo) || 0 : 0);
    const t = window.setTimeout(() => {
      firmaClienteRef.current?.clear();
      firmaTecnicoRef.current?.clear();
    }, 60);
    const prev = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    return () => {
      window.clearTimeout(t);
      document.body.style.overflow = prev;
    };
  }, [open, equipos]);

  if (!open) return null;

  const submit = () => {
    void (async () => {
      const m = motivo.trim();
      if (idEquipo <= 0) {
        await showAlert('Selecciona cuál equipo tendrá la salida temporal.', { title: 'Equipo requerido', icon: 'warning' });
        return;
      }
      if (!m) {
        await showAlert('Escribe el motivo de la salida temporal.', { title: 'Dato requerido', icon: 'warning' });
        return;
      }
      if (!firmaClienteRef.current?.hasStroke() || !firmaTecnicoRef.current?.hasStroke()) {
        await showAlert('Se requieren las firmas del cliente y del tecnico.', {
          title: 'Firmas requeridas',
          icon: 'warning',
        });
        return;
      }
      onConfirm({
        id_equipo: idEquipo,
        motivo: m,
        firma_cliente: firmaClienteRef.current.getDataUrl(),
        firma_tecnico: firmaTecnicoRef.current.getDataUrl(),
      });
    })();
  };

  return (
    <div
      className="fixed inset-0 z-[10100] flex items-center justify-center p-2"
      style={{ background: 'rgba(2,6,23,0.75)' }}
      role="dialog"
      aria-modal="true"
      aria-labelledby="modalSalidaTemporalTitle"
    >
      <div
        className="flex w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
        style={{ maxHeight: 'min(96dvh, 96vh)' }}
      >
        <div className="shrink-0 border-b border-orange-200 bg-orange-50 px-4 py-3">
          <h3 id="modalSalidaTemporalTitle" className="text-base font-bold text-orange-900">
            Salida temporal del equipo
          </h3>
        </div>

        <div className="shrink-0 px-4 pb-2 pt-3">
          <label htmlFor="salidaTemporalEquipo" className="mb-1 block text-sm font-bold text-orange-900">
            Equipo que sale temporalmente <span className="text-red-600">*</span>
          </label>
          <select
            id="salidaTemporalEquipo"
            value={idEquipo || ''}
            onChange={(event) => setIdEquipo(Number(event.target.value) || 0)}
            disabled={busy}
            className="mb-3 min-h-11 w-full rounded-lg border-2 border-orange-400 bg-white px-3 py-2 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-orange-300"
          >
            <option value="">Selecciona un equipo</option>
            {equipos.map((equipo, index) => (
              <option key={equipo.id_equipo || index} value={Number(equipo.id_equipo) || 0} disabled={!equipo.id_equipo}>
                {`Equipo ${index + 1} — ${[equipo.marca, equipo.modelo, equipo.serie].filter(Boolean).join(' · ') || 'Sin descripción'}`}
              </option>
            ))}
          </select>
          <label htmlFor="motivoSalidaTemporalInput" className="mb-1 block text-sm font-bold text-orange-900">
            Motivo de salida
            {' '}
            <span className="text-red-600">*</span>
          </label>
          <textarea
            id="motivoSalidaTemporalInput"
            rows={2}
            maxLength={4000}
            value={motivo}
            onChange={(e) => setMotivo(e.target.value)}
            disabled={busy}
            placeholder="Escribe aqui el motivo de la salida temporal..."
            className="w-full resize-none rounded-lg border-2 border-orange-400 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-300"
            style={{ height: '3.6rem', minHeight: '3.6rem', maxHeight: '3.6rem' }}
          />
        </div>

        <div className="min-h-0 flex-1 overflow-auto px-4 pb-3">
          <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div className="text-center">
              <h4 className="mb-1 text-sm font-bold text-blue-900">Firma del cliente</h4>
              <SignaturePad ref={firmaClienteRef} minHeight={88} hideClear disabled={busy} />
              <button
                type="button"
                disabled={busy}
                className="mt-2 rounded bg-blue-600 px-3 py-1.5 text-xs text-white hover:bg-blue-700 disabled:opacity-50"
                onClick={() => firmaClienteRef.current?.clear()}
              >
                Limpiar
              </button>
            </div>
            <div className="text-center">
              <h4 className="mb-1 text-sm font-bold text-blue-900">Firma del tecnico</h4>
              <SignaturePad ref={firmaTecnicoRef} minHeight={88} hideClear disabled={busy} />
              <button
                type="button"
                disabled={busy}
                className="mt-2 rounded bg-blue-600 px-3 py-1.5 text-xs text-white hover:bg-blue-700 disabled:opacity-50"
                onClick={() => firmaTecnicoRef.current?.clear()}
              >
                Limpiar
              </button>
            </div>
          </div>
        </div>

        <div className="flex shrink-0 flex-wrap items-center justify-end gap-2 border-t border-slate-200 bg-white px-4 py-3">
          <button
            type="button"
            disabled={busy}
            onClick={onCancel}
            className="rounded-lg border-2 border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50 disabled:opacity-50"
          >
            Cancelar
          </button>
          <button
            type="button"
            disabled={busy}
            onClick={submit}
            className="inline-flex items-center rounded-lg bg-orange-600 px-5 py-2 text-sm font-bold text-white hover:bg-orange-700 disabled:opacity-50"
          >
            {busy ? <i className="fas fa-spinner fa-spin mr-2" /> : <i className="fas fa-save mr-2" />}
            Guardar
          </button>
        </div>
      </div>
    </div>
  );
}
