type Props = {
  observaciones: string[];
  readOnly: boolean;
  requerido?: boolean;
  fieldError?: string;
  onChange: (next: string[]) => void;
};

export default function ObservacionesSection({
  observaciones,
  readOnly,
  requerido = false,
  fieldError,
  onChange,
}: Props) {
  const list = observaciones.length ? observaciones : [''];

  const update = (idx: number, value: string) => {
    const next = [...list];
    next[idx] = value;
    onChange(next);
  };

  const add = () => onChange([...list, '']);

  const remove = (idx: number) => {
    if (list.length <= 1) {
      onChange(['']);
      return;
    }
    onChange(list.filter((_, i) => i !== idx));
  };

  return (
    <section className="rounded-r-lg border-l-4 border-blue-600 bg-blue-50 p-4 sm:pl-6">
      <h2 className="mb-6 flex items-center text-xl font-bold text-blue-900 sm:text-2xl">
        <i className="fas fa-sticky-note mr-3 text-blue-600" aria-hidden="true" />
        OBSERVACIONES
        {requerido ? <span className="ml-2 text-red-600">*</span> : null}
      </h2>
      <div className="space-y-3">
        {list.map((obs, idx) => (
          <div key={idx} className="flex items-center">
            <span className="mr-3 font-bold text-blue-700">{idx + 1}.</span>
            <input
              type="text"
              className={`flex-1 rounded-lg border px-3 py-2 ${
                idx === 0 && fieldError
                  ? 'border-red-400 bg-red-50 focus:border-red-600 focus:outline-none'
                  : 'border-blue-300'
              }`}
              disabled={readOnly}
              value={obs}
              aria-label={`Observación ${idx + 1}`}
              aria-invalid={idx === 0 ? Boolean(fieldError) : undefined}
              data-anlux-field={`observacion.${idx}`}
              onChange={(e) => update(idx, e.target.value.toUpperCase())}
              placeholder={idx === 0 ? (requerido ? 'Observación (obligatoria)' : 'Primera observación') : 'Observación'}
            />
            {!readOnly ? (
              <>
                {idx === list.length - 1 ? (
                  <button
                    type="button"
                    className="ml-2 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-blue-300 bg-white text-blue-600 shadow-sm transition-colors duration-150 hover:bg-blue-50 hover:text-blue-800"
                    onClick={add}
                    title="Agregar observación"
                    aria-label="Agregar observación"
                  >
                    <i className="fas fa-plus" aria-hidden="true" />
                  </button>
                ) : null}
                {list.length > 1 ? (
                  <button
                    type="button"
                    className="ml-1.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-red-300 bg-white text-red-600 shadow-sm transition-colors duration-150 hover:bg-red-50 hover:text-red-800"
                    onClick={() => remove(idx)}
                    title={`Quitar observación ${idx + 1}`}
                    aria-label={`Quitar observación ${idx + 1}`}
                  >
                    <i className="fas fa-trash" aria-hidden="true" />
                  </button>
                ) : null}
              </>
            ) : null}
          </div>
        ))}
        {fieldError ? (
          <p className="text-xs font-semibold text-red-700" role="alert">
            {fieldError}
          </p>
        ) : null}
      </div>
    </section>
  );
}
