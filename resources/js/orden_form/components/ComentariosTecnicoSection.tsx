type Props = {
  comentarios: string;
  readOnly: boolean;
  titulo?: string;
  onChange: (value: string) => void;
};

export default function ComentariosTecnicoSection({
  comentarios,
  readOnly,
  titulo = 'COMENTARIOS DEL TECNICO',
  onChange,
}: Props) {
  return (
    <section className="rounded-r-lg border-l-4 border-blue-500 bg-blue-50 p-4 sm:pl-6">
      <h2 className="mb-4 flex items-center text-xl font-bold text-blue-900 sm:text-2xl">
        <i className="fas fa-comment mr-3 text-blue-500" />
        {titulo}
      </h2>
      <textarea
        name="comentariosTecnico"
        data-anlux-field="comentariosTecnico"
        rows={4}
        disabled={readOnly}
        value={comentarios}
        onChange={(e) => onChange(e.target.value.toUpperCase())}
        placeholder="Comentarios del tecnico sobre el equipo..."
        className="w-full rounded-lg border-2 border-blue-300 px-4 py-3 uppercase focus:border-blue-700 focus:outline-none disabled:bg-slate-100"
      />
    </section>
  );
}
