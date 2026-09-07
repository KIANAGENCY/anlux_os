import type { EstatusRadio } from '../types';
import { abrirPdfOrdenSinCache } from '../api';

type EditStatusModalProps = {
  open: boolean;
  ordenId: string;
  folio: string;
  selected: EstatusRadio;
  origen: EstatusRadio;
  firmasRecepcionOk: boolean;
  saving: boolean;
  onChange: (v: EstatusRadio) => void;
  onClose: () => void;
  onSave: () => void;
};

const OPTIONS: { value: EstatusRadio; label: string }[] = [
  { value: 'rojo', label: '🔴 Recepción' },
  { value: 'naranja', label: '🟠 En proceso' },
  { value: 'amarillo', label: '🟡 Terminado' },
];

export function EditStatusModal({
  open,
  ordenId,
  folio,
  selected,
  origen,
  firmasRecepcionOk,
  saving,
  onChange,
  onClose,
  onSave,
}: EditStatusModalProps) {
  if (!open) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-3">
      <div
        className="max-h-[90vh] w-full overflow-y-auto rounded-lg bg-white p-5 shadow-2xl sm:p-8"
        style={{ maxWidth: 420, width: 'min(420px, 92vw)' }}
      >
        <h3 className="mb-6 flex items-center justify-center text-center text-xl font-bold text-blue-700 sm:text-2xl">
          <i className="mr-3 fas fa-edit" />
          Editar Estatus
        </h3>
        <div className="mb-6 rounded-lg bg-blue-50 p-4">
          <div className="flex flex-col gap-3">
            {OPTIONS.map((opt) => {
              const disabled =
                !firmasRecepcionOk
                && (opt.value === 'naranja' || opt.value === 'amarillo')
                && opt.value !== origen;
              return (
                <label key={opt.value} className={`flex items-center gap-2 ${disabled ? 'opacity-50' : ''}`}>
                  <input
                    type="radio"
                    name="editEstatus"
                    value={opt.value}
                    checked={selected === opt.value}
                    disabled={disabled}
                    onChange={() => onChange(opt.value)}
                  />
                  {opt.label}
                </label>
              );
            })}
          </div>
        </div>
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div className="grid w-full grid-cols-1 gap-2 sm:flex sm:w-auto sm:flex-wrap">
            <button
              type="button"
              onClick={() => {
                onClose();
                void abrirPdfOrdenSinCache(ordenId, true);
              }}
              className="flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700"
            >
              <i className="fas fa-file-pdf" />
              Ver PDF
            </button>
            <button
              type="button"
              onClick={() => void abrirPdfOrdenSinCache(ordenId, false)}
              className="flex items-center justify-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-green-700"
              title={folio || undefined}
            >
              <i className="fas fa-download" />
              Descargar
            </button>
          </div>
          <div className="grid w-full grid-cols-1 gap-3 sm:w-auto sm:grid-cols-2">
            <button
              type="button"
              onClick={onClose}
              className="rounded-lg bg-gray-400 px-4 py-2 text-white transition hover:bg-gray-500"
            >
              Cancelar
            </button>
            <button
              type="button"
              disabled={saving}
              onClick={onSave}
              className="rounded-lg bg-blue-600 px-4 py-2 text-white transition hover:bg-blue-700 disabled:opacity-60"
            >
              {saving ? 'Guardando…' : 'Guardar'}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
