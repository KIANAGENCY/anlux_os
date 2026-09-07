import { useEffect, useState } from 'react';
import type { EstatusFiltro } from '../types';

type FilterModalProps = {
  open: boolean;
  startDate: string;
  endDate: string;
  estatus: EstatusFiltro;
  onClose: () => void;
  onApply: (next: { startDate: string; endDate: string; estatus: EstatusFiltro }) => void;
};

const OPTIONS: { value: EstatusFiltro; label: string }[] = [
  { value: 'todos', label: 'Todos' },
  { value: 'rojo', label: '🔴 Recepción' },
  { value: 'naranja', label: '🟠 En proceso' },
  { value: 'amarillo', label: '🟡 Terminado' },
  { value: 'verde', label: '🟢 Entregado' },
];

export function FilterModal({
  open,
  startDate,
  endDate,
  estatus,
  onClose,
  onApply,
}: FilterModalProps) {
  const [draftStart, setDraftStart] = useState(startDate);
  const [draftEnd, setDraftEnd] = useState(endDate);
  const [draftEstatus, setDraftEstatus] = useState<EstatusFiltro>(estatus);

  useEffect(() => {
    if (open) {
      setDraftStart(startDate);
      setDraftEnd(endDate);
      setDraftEstatus(estatus);
    }
  }, [open, startDate, endDate, estatus]);

  if (!open) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-3">
      <div className="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-lg bg-white p-5 shadow-2xl sm:p-8">
        <h3 className="mb-6 flex items-center text-xl font-bold text-blue-700 sm:text-2xl">
          <i className="mr-3 fas fa-filter" />
          Filtrar órdenes
        </h3>
        <div className="mb-4">
          <label className="mb-2 block text-sm font-semibold text-blue-900">Desde:</label>
          <input
            type="date"
            value={draftStart}
            onChange={(e) => setDraftStart(e.target.value)}
            className="w-full rounded-lg border-2 border-blue-300 px-4 py-2 focus:border-blue-700 focus:outline-none"
          />
        </div>
        <div className="mb-4">
          <label className="mb-2 block text-sm font-semibold text-blue-900">Hasta:</label>
          <input
            type="date"
            value={draftEnd}
            onChange={(e) => setDraftEnd(e.target.value)}
            className="w-full rounded-lg border-2 border-blue-300 px-4 py-2 focus:border-blue-700 focus:outline-none"
          />
        </div>
        <div className="mb-6 rounded-lg bg-blue-50 p-4">
          {OPTIONS.map((opt) => (
            <label key={opt.value} className="mb-2 block last:mb-0">
              <input
                type="radio"
                name="filtro"
                value={opt.value}
                checked={draftEstatus === opt.value}
                onChange={() => setDraftEstatus(opt.value)}
                className="mr-2"
              />
              {opt.label}
            </label>
          ))}
        </div>
        <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
          <button
            type="button"
            onClick={onClose}
            className="rounded-lg bg-gray-400 px-6 py-2 text-white transition hover:bg-gray-500"
          >
            Cerrar
          </button>
          <button
            type="button"
            onClick={() => onApply({ startDate: draftStart, endDate: draftEnd, estatus: draftEstatus })}
            className="rounded-lg bg-blue-600 px-6 py-2 text-white transition hover:bg-blue-700"
          >
            Aplicar
          </button>
        </div>
      </div>
    </div>
  );
}
