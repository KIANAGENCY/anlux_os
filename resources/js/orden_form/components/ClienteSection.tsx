import type { ClienteState } from '../types';

const inputCls =
  'w-full rounded-lg border-2 border-blue-300 px-4 py-2 focus:border-blue-700 focus:bg-blue-50 focus:outline-none';
const inputErrorCls =
  'w-full rounded-lg border-2 border-red-400 bg-red-50 px-4 py-2 focus:border-red-600 focus:outline-none';
const labelCls = 'mb-2 block text-sm font-semibold text-blue-900';
const readonlyCls =
  'w-full cursor-not-allowed rounded-lg border-2 border-blue-200 bg-blue-100 px-4 py-2 text-blue-700';

type Props = {
  value: ClienteState;
  readOnly: boolean;
  fieldErrors?: Partial<Record<'nombreCliente' | 'poblacion' | 'correo', string>>;
  onChange: (next: ClienteState) => void;
};

const hintCls = 'mt-1 text-xs font-semibold text-red-700';

export default function ClienteSection({
  value,
  readOnly,
  fieldErrors,
  onChange,
}: Props) {
  const set = <K extends keyof ClienteState>(key: K, v: ClienteState[K]) => {
    onChange({ ...value, [key]: v });
  };

  const disabled = readOnly;

  return (
    <section className="rounded-r-lg border-l-4 border-blue-700 bg-blue-50 p-4 sm:pl-6">
      <h2 className="mb-6 flex items-center text-xl font-bold text-blue-900 sm:text-2xl">
        <i className="fas fa-user-tie mr-3 text-blue-700" aria-hidden="true" />
        DATOS DEL CLIENTE
      </h2>

      <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
        <div>
          <label className={labelCls} htmlFor="cliente-nombre">
            Nombre o Razón Social *
          </label>
          <input
            id="cliente-nombre"
            type="text"
            className={
              disabled ? readonlyCls : fieldErrors?.nombreCliente ? inputErrorCls : inputCls
            }
            value={value.nombreCliente}
            disabled={disabled}
            aria-invalid={Boolean(fieldErrors?.nombreCliente)}
            aria-describedby={fieldErrors?.nombreCliente ? 'cliente-nombre-error' : undefined}
            data-anlux-field="cliente.nombreCliente"
            onChange={(e) => set('nombreCliente', e.target.value.toUpperCase())}
            placeholder="Nombre completo o nombre de empresa"
          />
          {fieldErrors?.nombreCliente ? (
            <p id="cliente-nombre-error" className={hintCls} role="alert">
              {fieldErrors.nombreCliente}
            </p>
          ) : null}
        </div>

        <div>
          <label className={labelCls} htmlFor="cliente-folio">
            Orden de Servicio (Folio) *
          </label>
          <input id="cliente-folio" type="text" className={readonlyCls} value={value.folio} readOnly />
        </div>

        <div>
          <label className={labelCls} htmlFor="cliente-direccion">
            Dirección <span className="font-normal text-blue-700">(opcional)</span>
          </label>
          <input
            id="cliente-direccion"
            type="text"
            className={disabled ? readonlyCls : inputCls}
            value={value.direccion}
            disabled={disabled}
            onChange={(e) => set('direccion', e.target.value.toUpperCase())}
            placeholder="Calle y número"
          />
        </div>

        <div>
          <label className={labelCls} htmlFor="cliente-telefono">
            Celular <span className="font-normal text-blue-700">(opcional)</span>
          </label>
          <input
            id="cliente-telefono"
            type="tel"
            inputMode="numeric"
            maxLength={20}
            className={disabled ? readonlyCls : inputCls}
            value={value.telefono}
            disabled={disabled}
            onChange={(e) => set('telefono', e.target.value.replace(/\D/g, '').slice(0, 20))}
            placeholder="Opcional - 10 dígitos, ej. 6121942057"
          />
        </div>

        <div>
          <label className={labelCls} htmlFor="cliente-correo">
            Correo electrónico <span className="font-normal text-blue-700">(opcional)</span>
          </label>
          <input
            id="cliente-correo"
            type="email"
            maxLength={120}
            className={
              disabled ? readonlyCls : fieldErrors?.correo ? inputErrorCls : inputCls
            }
            value={value.correo}
            disabled={disabled}
            aria-invalid={Boolean(fieldErrors?.correo)}
            aria-describedby={fieldErrors?.correo ? 'cliente-correo-error' : undefined}
            data-anlux-field="cliente.correo"
            onChange={(e) => set('correo', e.target.value)}
            onBlur={(e) => set('correo', e.target.value.trim().toLowerCase())}
            placeholder="cliente@ejemplo.com"
          />
          {fieldErrors?.correo ? (
            <p id="cliente-correo-error" className={hintCls} role="alert">
              {fieldErrors.correo}
            </p>
          ) : null}
        </div>

        <div>
          <label className={labelCls} htmlFor="cliente-poblacion">
            Población / Ciudad *
          </label>
          <input
            id="cliente-poblacion"
            type="text"
            maxLength={80}
            className={
              disabled ? readonlyCls : fieldErrors?.poblacion ? inputErrorCls : inputCls
            }
            value={value.poblacion}
            disabled={disabled}
            aria-invalid={Boolean(fieldErrors?.poblacion)}
            aria-describedby={fieldErrors?.poblacion ? 'cliente-poblacion-error' : undefined}
            data-anlux-field="cliente.poblacion"
            onChange={(e) => set('poblacion', e.target.value.toUpperCase())}
            placeholder="Ciudad o municipio"
          />
          {fieldErrors?.poblacion ? (
            <p id="cliente-poblacion-error" className={hintCls} role="alert">
              {fieldErrors.poblacion}
            </p>
          ) : null}
        </div>

        <div>
          <label className={labelCls} htmlFor="cliente-fecha-entrada">
            Fecha de Entrada *
          </label>
          <input id="cliente-fecha-entrada" type="datetime-local" className={readonlyCls} value={value.fechaEntrada} readOnly />
        </div>
      </div>
    </section>
  );
}
