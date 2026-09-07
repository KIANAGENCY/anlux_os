type Props = {
  observaciones: string[];
  readOnly: boolean;
  requerido?: boolean;
  onChange: (next: string[]) => void;
};

export default function ObservacionesSection({ observaciones, readOnly, requerido = false, onChange }: Props) {
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
        <i className="fas fa-sticky-note mr-3 text-blue-600" />
        OBSERVACIONES
        {requerido ? <span className="ml-2 text-red-600">*</span> : null}
      </h2>
      <div className="space-y-3">
        {list.map((obs, idx) => (
          <div key={idx} className="flex items-start">
            <span className="mr-3 font-bold text-blue-700">{idx + 1}.</span>
            <input
              type="text"
              className="flex-1 rounded-lg border border-blue-300 px-3 py-2"
              disabled={readOnly}
              value={obs}
              data-anlux-field={`observacion.${idx}`}
              onChange={(e) => update(idx, e.target.value.toUpperCase())}
              placeholder={idx === 0 ? (requerido ? 'Observacion (obligatoria)' : 'Primera observacion') : 'Observacion'}
            />
            {!readOnly ? (
              <>
                {idx === list.length - 1 ? (
                  <button type="button" className="ml-2 font-bold text-blue-600 hover:text-blue-800" onClick={add} title="Agregar observacion">
                    <i className="fas fa-plus" />
                  </button>
                ) : null}
                {list.length > 1 ? (
                  <button type="button" className="ml-2 font-bold text-red-600 hover:text-red-800" onClick={() => remove(idx)} title="Quitar">
                    <i className="fas fa-trash" />
                  </button>
                ) : null}
              </>
            ) : null}
          </div>
        ))}
      </div>
    </section>
  );
}
