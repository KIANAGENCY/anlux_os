import type { ClienteState } from '../types';

const inputCls =
  'w-full rounded-lg border-2 border-blue-300 px-4 py-2 focus:border-blue-700 focus:bg-blue-50 focus:outline-none';
const labelCls = 'mb-2 block text-sm font-semibold text-blue-900';
const readonlyCls =
  'w-full cursor-not-allowed rounded-lg border-2 border-blue-200 bg-blue-100 px-4 py-2 text-blue-700';

type Props = {
  value: ClienteState;
  readOnly: boolean;
  onChange: (next: ClienteState) => void;
};

export default function ClienteSection({ value, readOnly, onChange }: Props) {
  const set = <K extends keyof ClienteState>(key: K, v: ClienteState[K]) => {
    onChange({ ...value, [key]: v });
  };

  const disabled = readOnly;

  return (
    <section className="rounded-r-lg border-l-4 border-blue-700 bg-blue-50 p-4 sm:pl-6">
      <h2 className="mb-6 flex items-center text-xl font-bold text-blue-900 sm:text-2xl">
        <i className="fas fa-user-tie mr-3 text-blue-700" />
        DATOS DEL CLIENTE
      </h2>

      <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
        <div>
          <label className={labelCls}>Nombre o Razon Social *</label>
          <input
            type="text"
            className={disabled ? readonlyCls : inputCls}
            value={value.nombreCliente}
            disabled={disabled}
            data-anlux-field="cliente.nombreCliente"
            onChange={(e) => set('nombreCliente', e.target.value.toUpperCase())}
            placeholder="Nombre completo o nombre de empresa"
          />
        </div>

        <div>
          <label className={labelCls}>Orden de Servicio (Folio) *</label>
          <input type="text" className={readonlyCls} value={value.folio} readOnly />
        </div>

        <div>
          <label className={labelCls}>
            Direccion <span className="font-normal text-blue-700">(opcional)</span>
          </label>
          <input
            type="text"
            className={disabled ? readonlyCls : inputCls}
            value={value.direccion}
            disabled={disabled}
            onChange={(e) => set('direccion', e.target.value.toUpperCase())}
            placeholder="Calle y numero"
          />
        </div>

        <div>
          <label className={labelCls}>
            Celular <span className="font-normal text-blue-700">(opcional)</span>
          </label>
          <input
            type="tel"
            inputMode="numeric"
            maxLength={20}
            className={disabled ? readonlyCls : inputCls}
            value={value.telefono}
            disabled={disabled}
            onChange={(e) => set('telefono', e.target.value.replace(/\D/g, '').slice(0, 20))}
            placeholder="Opcional - 10 digitos, ej. 6121942057"
          />
        </div>

        <div>
          <label className={labelCls}>
            Correo electronico <span className="font-normal text-blue-700">(opcional)</span>
          </label>
          <input
            type="email"
            maxLength={120}
            className={disabled ? readonlyCls : inputCls}
            value={value.correo}
            disabled={disabled}
            data-anlux-field="cliente.correo"
            onChange={(e) => set('correo', e.target.value)}
            onBlur={(e) => set('correo', e.target.value.trim().toLowerCase())}
            placeholder="cliente@ejemplo.com"
          />
        </div>

        <div>
          <label className={labelCls}>Poblacion/Ciudad *</label>
          <input
            type="text"
            maxLength={80}
            className={disabled ? readonlyCls : inputCls}
            value={value.poblacion}
            disabled={disabled}
            data-anlux-field="cliente.poblacion"
            onChange={(e) => set('poblacion', e.target.value.toUpperCase())}
            placeholder="Ciudad o municipio"
          />
        </div>

        <div>
          <label className={labelCls}>Fecha de Entrada *</label>
          <input type="datetime-local" className={readonlyCls} value={value.fechaEntrada} readOnly />
        </div>
      </div>
    </section>
  );
}
