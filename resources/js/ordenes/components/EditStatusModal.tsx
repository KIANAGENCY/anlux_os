import type { EstatusRadio } from '../types';
import { abrirPdfOrdenSinCache } from '../api';
import { IconDownload, IconFilePdf, IconSpinner } from '../../shared/icons';

type EditStatusModalProps = {
  open: boolean;
  ordenId: string;
  folio: string;
  selected: EstatusRadio;
  origen: EstatusRadio;
  salidaTemporalActiva: boolean;
  saving: boolean;
  onChange: (v: EstatusRadio) => void;
  onClose: () => void;
  onSave: () => void;
};

const OPTIONS: { value: EstatusRadio; label: string }[] = [
  { value: 'rojo', label: 'Recepción' },
  { value: 'naranja', label: 'En proceso' },
  { value: 'amarillo', label: 'Terminado' },
];

export function EditStatusModal({
  open,
  ordenId,
  folio,
  selected,
  origen,
  salidaTemporalActiva,
  saving,
  onChange,
  onClose,
  onSave,
}: EditStatusModalProps) {
  if (!open) return null;

  const folioText = folio ? ` de orden ${folio}` : '';

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-3"
      role="dialog"
      aria-modal="true"
      aria-labelledby="modalEditStatusTitle"
    >
      <div
        className="max-h-[90vh] w-full overflow-y-auto rounded-xl bg-white p-5 shadow-2xl sm:p-7"
        style={{ maxWidth: 440, width: 'min(440px, 92vw)' }}
      >
        <h3 id="modalEditStatusTitle" className="mb-5 text-center text-xl font-bold text-slate-900">
          Cambiar estatus{folioText}
        </h3>
        <div className="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-4">
          <div className="flex flex-col gap-3">
            {OPTIONS.map((opt) => {
              const disabledSalida = salidaTemporalActiva && opt.value === 'amarillo' && opt.value !== origen;
              const disabled = disabledSalida;
              return (
                <label key={opt.value} className={`flex cursor-pointer items-center gap-2 text-sm font-semibold text-slate-800 ${disabled ? 'cursor-not-allowed opacity-50' : ''}`}>
                  <input
                    type="radio"
                    name="editEstatus"
                    value={opt.value}
                    checked={selected === opt.value}
                    disabled={disabled}
                    onChange={() => onChange(opt.value)}
                    className="h-4 w-4 text-blue-600 focus:ring-blue-500"
                  />
                  {opt.label}
                  {disabledSalida ? (
                    <span className="text-xs font-normal text-orange-900">(salida temporal activa)</span>
                  ) : null}
                </label>
              );
            })}
          </div>
          {salidaTemporalActiva ? (
            <p className="mt-3 text-xs font-medium text-orange-900">
              Hay un equipo en salida temporal. No puedes pasar la orden a Terminado hasta el regreso.
            </p>
          ) : null}
        </div>
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div className="grid w-full grid-cols-2 gap-2 sm:flex sm:w-auto">
            <button
              type="button"
              onClick={() => {
                onClose();
                void abrirPdfOrdenSinCache(ordenId, true);
              }}
              aria-label={`Ver PDF${folioText}`}
              className="anlux-btn-secondary min-h-11"
            >
              <IconFilePdf size={18} />
              PDF
            </button>
            <button
              type="button"
              onClick={() => void abrirPdfOrdenSinCache(ordenId, false)}
              aria-label={`Descargar PDF${folioText}`}
              title={folio || undefined}
              className="anlux-btn-secondary min-h-11"
            >
              <IconDownload size={18} />
              Descargar
            </button>
          </div>
          <div className="grid w-full grid-cols-2 gap-2 sm:w-auto">
            <button
              type="button"
              onClick={onClose}
              className="anlux-btn-secondary min-h-11"
            >
              Cancelar
            </button>
            <button
              type="button"
              disabled={saving}
              onClick={onSave}
              className="anlux-btn-primary min-h-11"
            >
              {saving ? <IconSpinner size={18} /> : null}
              {saving ? 'Guardando…' : 'Guardar'}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
