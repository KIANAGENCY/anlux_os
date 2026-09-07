import { useEffect, useRef, useState } from 'react';
import { useAnluxDialog } from '../../shared/nav/ui';
import SignaturePad from './SignaturePad';
import type { EntregaEquipoPayload, EquipoForm, SignaturePadHandle } from '../types';

type ConfirmProps = {
  open: boolean;
  equipo: EquipoForm | null;
  equipoIndice: number;
  saldoPendiente: number;
  busy?: boolean;
  onCancel: () => void;
  onLiquidar: () => void;
  onConfirmTerminado: () => void;
  onNeedFirmas: () => void;
};

export function EntregaConfirmModal({
  open,
  equipo,
  equipoIndice,
  saldoPendiente,
  busy = false,
  onCancel,
  onLiquidar,
  onConfirmTerminado,
  onNeedFirmas,
}: ConfirmProps) {
  const { showAlert } = useAnluxDialog();
  const [chkTerminado, setChkTerminado] = useState(false);
  const [chkEntregado, setChkEntregado] = useState(false);
  const [showSaldoWarn, setShowSaldoWarn] = useState(false);

  const acciones = Number(equipo?.acciones) || 0;

  useEffect(() => {
    if (!open || !equipo) return;
    setChkTerminado(acciones >= 1);
    setChkEntregado(acciones >= 2);
    setShowSaldoWarn(false);
    const prev = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    return () => {
      document.body.style.overflow = prev;
    };
  }, [open, equipo, acciones]);

  if (!open || !equipo) return null;

  const terminadoLocked = acciones >= 1;
  const puedeEntregar = chkTerminado || acciones >= 1;

  const onToggleTerminado = (checked: boolean) => {
    void (async () => {
      if (terminadoLocked) {
        setChkTerminado(true);
        return;
      }
      if (checked && saldoPendiente > 0.009) {
        setShowSaldoWarn(true);
        setChkTerminado(false);
        await showAlert(
          `Queda un saldo pendiente de ${saldoPendiente.toFixed(2)}. Liquidalo antes de marcar Terminado.`,
          { title: 'Saldo pendiente', icon: 'warning' },
        );
        return;
      }
      setShowSaldoWarn(false);
      setChkTerminado(checked);
      if (!checked) setChkEntregado(false);
    })();
  };

  const onToggleEntregado = (checked: boolean) => {
    void (async () => {
      if (!puedeEntregar && checked) {
        setChkEntregado(false);
        await showAlert(
          'Primero marca Terminado (uso interno). En cuanto lo marques, Entregado se habilita.',
          { title: 'Estatus', icon: 'warning' },
        );
        return;
      }
      setChkEntregado(checked);
    })();
  };

  const onGuardar = () => {
    void (async () => {
      if (!chkTerminado && !chkEntregado) {
        await showAlert('Selecciona al menos un estatus (Terminado o Entregado).', {
          title: 'Estatus',
          icon: 'warning',
        });
        return;
      }
      if (chkEntregado && !puedeEntregar) {
        await showAlert('Primero marca Terminado (uso interno). Luego podras marcar Entregado.', {
          title: 'Estatus',
          icon: 'warning',
        });
        return;
      }
      if (chkTerminado && acciones >= 1 && !chkEntregado) {
        await showAlert(
          'Este equipo ya esta Terminado. Marca Entregado para firmar y enviar la OS de este equipo.',
          { title: 'Estatus', icon: 'info' },
        );
        return;
      }
      if (chkEntregado) {
        onNeedFirmas();
        return;
      }
      if (saldoPendiente > 0.009) {
        await showAlert(
          `Queda un saldo pendiente de ${saldoPendiente.toFixed(2)}. Liquidalo antes de marcar Terminado.`,
          { title: 'Saldo pendiente', icon: 'warning' },
        );
        return;
      }
      onConfirmTerminado();
    })();
  };

  return (
    <div
      className="fixed inset-0 z-[10160] flex items-center justify-center bg-slate-950/80 p-4"
      role="dialog"
      aria-modal="true"
    >
      <div className="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div className="bg-blue-600 px-5 py-4">
          <h3 className="flex items-center justify-center gap-2 text-center text-lg font-bold text-white">
            <i className="fas fa-truck" />
            Entrega por Equipo
          </h3>
        </div>
        <div className="space-y-4 p-5">
          <p className="text-center text-sm text-slate-600">
            ¿Entregar el equipo #
            {equipoIndice}
            {' '}
            individualmente?
          </p>
          <p className="text-center text-xs text-slate-500">
            {acciones >= 2
              ? <>Estado actual: <strong>Entregado</strong>.</>
              : acciones >= 1
                ? <>Estado actual: <strong>Terminado</strong>. Marca <strong>Entregado</strong> para firmar y notificar.</>
                : <>Estado actual: <strong>pendiente</strong>. Marca <strong>Terminado</strong> (uso interno).</>}
          </p>
          <div className="grid grid-cols-2 gap-2">
            <label className={`flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2.5 transition ${terminadoLocked ? 'border-amber-300 bg-amber-50' : 'border-slate-200 hover:border-blue-400 hover:bg-blue-50'}`}>
              <input
                type="checkbox"
                checked={chkTerminado}
                disabled={busy || terminadoLocked}
                onChange={(e) => onToggleTerminado(e.target.checked)}
                className="h-4 w-4 rounded border-blue-600"
              />
              <span className="text-sm font-medium text-blue-900">
                Marcar como Terminado
                <br />
                <span className="text-[10px] font-normal text-slate-500">(uso interno)</span>
              </span>
            </label>
            <label className={`flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2.5 transition ${!puedeEntregar ? 'opacity-60' : 'border-slate-200 hover:border-blue-400 hover:bg-blue-50'}`}>
              <input
                type="checkbox"
                checked={chkEntregado}
                disabled={busy || !puedeEntregar}
                onChange={(e) => onToggleEntregado(e.target.checked)}
                className="h-4 w-4 rounded border-blue-600"
              />
              <span className="text-sm font-medium text-blue-900">
                Marcar como Entregado
                <br />
                <span className="text-[10px] font-normal text-slate-500">(notifica cliente)</span>
              </span>
            </label>
          </div>
          {showSaldoWarn ? (
            <div className="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
              <p className="flex items-start gap-2">
                <i className="fas fa-exclamation-triangle mt-0.5 text-amber-500" />
                <span>Para marcar como Terminado, el saldo pendiente debe estar liquidado.</span>
              </p>
              <button
                type="button"
                className="mt-2 text-xs font-semibold text-blue-600 underline hover:text-blue-800"
                onClick={onLiquidar}
              >
                Liquidar Saldo Ahora
              </button>
            </div>
          ) : null}
          <div className="rounded-lg border border-blue-200 bg-blue-50 p-3">
            <p className="mb-1 text-xs font-bold uppercase tracking-wide text-blue-700">
              <i className="fas fa-microchip mr-1" />
              Equipo a entregar
            </p>
            <table className="w-full text-sm text-slate-700">
              <tbody>
                <tr>
                  <td className="py-0.5 pr-2 text-slate-500">Marca</td>
                  <td className="py-0.5 font-semibold text-slate-800">{equipo.marca || '—'}</td>
                </tr>
                <tr>
                  <td className="py-0.5 pr-2 text-slate-500">Modelo</td>
                  <td className="py-0.5 font-semibold text-slate-800">{equipo.modelo || '—'}</td>
                </tr>
                <tr>
                  <td className="py-0.5 pr-2 text-slate-500">Serie</td>
                  <td className="py-0.5 font-semibold text-slate-800">{equipo.serie || '—'}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div className="flex justify-end gap-2 border-t border-slate-200 p-4">
          <button
            type="button"
            disabled={busy}
            onClick={onCancel}
            className="rounded-lg border-2 border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
          >
            Cancelar
          </button>
          <button
            type="button"
            disabled={busy}
            onClick={onGuardar}
            className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 disabled:opacity-50"
          >
            {busy ? <i className="fas fa-spinner fa-spin mr-2" /> : null}
            Continuar
          </button>
        </div>
      </div>
    </div>
  );
}

type FirmasProps = {
  open: boolean;
  equipo: EquipoForm | null;
  nombreClienteTitular: string;
  busy?: boolean;
  onCancel: () => void;
  onConfirm: (payload: Pick<EntregaEquipoPayload, 'recibidoCliente' | 'entregaQuienRecibe' | 'firmaCliente' | 'firmaTecnico'>) => void;
};

export function EntregaFirmasModal({
  open,
  equipo,
  nombreClienteTitular,
  busy = false,
  onCancel,
  onConfirm,
}: FirmasProps) {
  const { showAlert, showConfirm } = useAnluxDialog();
  const [quien, setQuien] = useState<'cliente' | 'tercero'>('cliente');
  const [nombreReceptor, setNombreReceptor] = useState('');
  const firmaClienteRef = useRef<SignaturePadHandle | null>(null);
  const firmaTecnicoRef = useRef<SignaturePadHandle | null>(null);

  useEffect(() => {
    if (!open) return;
    const tipo = String(equipo?.entrega_receptor_tipo || '').toLowerCase() === 'tercero' ? 'tercero' : 'cliente';
    setQuien(tipo);
    const prevNombre = String(equipo?.entrega_recibido_cliente || '').trim();
    setNombreReceptor(prevNombre || (tipo === 'cliente' ? nombreClienteTitular : ''));
    const t = window.setTimeout(() => {
      firmaClienteRef.current?.clear();
      firmaTecnicoRef.current?.clear();
    }, 80);
    const prev = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    return () => {
      window.clearTimeout(t);
      document.body.style.overflow = prev;
    };
  }, [open, equipo, nombreClienteTitular]);

  useEffect(() => {
    if (!open) return;
    if (quien === 'cliente' && !nombreReceptor.trim()) {
      setNombreReceptor(nombreClienteTitular);
    }
  }, [quien, open, nombreClienteTitular, nombreReceptor]);

  if (!open || !equipo) return null;

  const submit = async () => {
    const nombre = nombreReceptor.trim().toUpperCase();
    if (nombre.length < 3) {
      await showAlert(
        quien === 'tercero'
          ? 'Escribe el nombre completo del tercero que recoge el equipo.'
          : 'Indica el nombre de quien recibe el equipo.',
        { title: 'Dato requerido', icon: 'warning' },
      );
      return;
    }
    if (quien === 'tercero') {
      const ok = await showConfirm(`¿Confirmas que ${nombre} recogera este equipo como tercero?`, {
        title: 'Confirmar tercero',
        icon: 'warning',
        confirmText: 'Sí, confirmar',
        cancelText: 'Cancelar',
      });
      if (!ok) return;
    }
    if (!firmaClienteRef.current?.hasStroke() || !firmaTecnicoRef.current?.hasStroke()) {
      await showAlert('Se requieren las firmas de quien recibe y del tecnico.', {
        title: 'Firmas requeridas',
        icon: 'warning',
      });
      return;
    }
    onConfirm({
      recibidoCliente: nombre,
      entregaQuienRecibe: quien,
      firmaCliente: firmaClienteRef.current.getDataUrl(),
      firmaTecnico: firmaTecnicoRef.current.getDataUrl(),
    });
  };

  return (
    <div
      className="fixed inset-0 z-[10170] flex items-center justify-center bg-slate-950/80 p-4"
      role="dialog"
      aria-modal="true"
    >
      <div
        className="flex w-full max-w-4xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl"
        style={{ maxHeight: '90vh' }}
      >
        <div className="flex shrink-0 flex-col gap-3 bg-blue-600 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h3 className="flex items-center gap-2 text-lg font-bold text-white">
              <i className="fas fa-file-signature" />
              Firmas de Entrega
            </h3>
            <p className="mt-0.5 text-xs text-blue-100 sm:text-sm">Confirma la entrega y registra las firmas.</p>
          </div>
          <div className="inline-flex max-w-full items-center gap-2 self-start rounded-lg border border-blue-400 px-3 py-2 text-xs text-white" style={{ background: 'rgba(29,78,216,.62)' }}>
            <span className="shrink-0 font-semibold text-blue-100">
              <i className="fas fa-microchip mr-1" />
              Equipo:
            </span>
            <span className="min-w-0 truncate font-bold">
              {equipo.marca}
              {' '}
              {equipo.modelo}
            </span>
          </div>
        </div>

        <div className="flex-1 space-y-4 overflow-y-auto p-4 sm:p-5">
          <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
              <div>
                <p className="mb-2 text-sm font-bold text-slate-800">¿Quien recoge el equipo?</p>
                <div className="flex gap-1 rounded-lg border border-slate-200 bg-slate-100 p-1">
                  <label className={`flex flex-1 cursor-pointer items-center justify-center rounded-md px-3 py-2 text-sm font-bold ${quien === 'cliente' ? 'bg-white text-blue-700 shadow' : 'text-slate-600'}`}>
                    <input
                      type="radio"
                      className="sr-only"
                      checked={quien === 'cliente'}
                      onChange={() => {
                        setQuien('cliente');
                        setNombreReceptor(nombreClienteTitular);
                      }}
                    />
                    Cliente titular
                  </label>
                  <label className={`flex flex-1 cursor-pointer items-center justify-center rounded-md px-3 py-2 text-sm font-bold ${quien === 'tercero' ? 'bg-white text-amber-700 shadow' : 'text-slate-600'}`}>
                    <input
                      type="radio"
                      className="sr-only"
                      checked={quien === 'tercero'}
                      onChange={() => {
                        setQuien('tercero');
                        setNombreReceptor('');
                      }}
                    />
                    Tercero
                  </label>
                </div>
              </div>
              <div>
                <label className="mb-2 block text-sm font-bold text-slate-800">
                  Nombre de quien recibe
                  {' '}
                  <span className="text-red-600">*</span>
                </label>
                <input
                  type="text"
                  maxLength={255}
                  value={nombreReceptor}
                  disabled={busy}
                  onChange={(e) => setNombreReceptor(e.target.value.toUpperCase())}
                  className="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold uppercase text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                  placeholder="Nombre completo de quien recoge"
                />
                {quien === 'tercero' ? (
                  <p className="mt-1.5 text-xs font-medium text-amber-700">
                    <i className="fas fa-circle-info mr-1" />
                    Captura el nombre completo del tercero; se confirmara antes de guardar.
                  </p>
                ) : null}
              </div>
            </div>
          </div>

          <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
              <div className="flex min-h-[3rem] items-center justify-between gap-3 border-b border-slate-200 bg-blue-50 px-4 py-2.5">
                <span className="text-sm font-bold text-blue-900">Firma de quien recibe</span>
                <button
                  type="button"
                  disabled={busy}
                  className="rounded-md border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-600 hover:bg-red-50 hover:text-red-600"
                  onClick={() => firmaClienteRef.current?.clear()}
                >
                  <i className="fas fa-eraser mr-1" />
                  Limpiar
                </button>
              </div>
              <div className="p-2">
                <SignaturePad ref={firmaClienteRef} minHeight={176} hideClear disabled={busy} />
              </div>
            </div>
            <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
              <div className="flex min-h-[3rem] items-center justify-between gap-3 border-b border-slate-200 bg-blue-50 px-4 py-2.5">
                <span className="text-sm font-bold text-blue-900">Firma del Tecnico</span>
                <button
                  type="button"
                  disabled={busy}
                  className="rounded-md border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-600 hover:bg-red-50 hover:text-red-600"
                  onClick={() => firmaTecnicoRef.current?.clear()}
                >
                  <i className="fas fa-eraser mr-1" />
                  Limpiar
                </button>
              </div>
              <div className="p-2">
                <SignaturePad ref={firmaTecnicoRef} minHeight={176} hideClear disabled={busy} />
              </div>
            </div>
          </div>
        </div>

        <div className="flex shrink-0 flex-col-reverse gap-2 border-t border-slate-200 bg-slate-50 px-4 py-3 sm:flex-row sm:justify-end sm:px-6">
          <button
            type="button"
            disabled={busy}
            onClick={onCancel}
            className="min-h-[2.75rem] rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-100"
          >
            Cancelar
          </button>
          <button
            type="button"
            disabled={busy}
            onClick={() => void submit()}
            className="inline-flex min-h-[2.75rem] items-center justify-center gap-2 rounded-lg bg-green-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-green-700 disabled:opacity-50"
          >
            {busy ? <i className="fas fa-spinner fa-spin" /> : null}
            <span>Guardar y Enviar</span>
            <span className="ml-1 inline-flex items-center gap-1.5 border-l border-green-500 pl-3">
              <i className="fab fa-whatsapp" />
              <i className="far fa-envelope" />
            </span>
          </button>
        </div>
      </div>
    </div>
  );
}
