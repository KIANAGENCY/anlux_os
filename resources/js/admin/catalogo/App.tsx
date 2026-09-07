import { useState } from 'react';
import { csrfToken, readPageProps } from '../../shared/http';

type Servicio = {
  clave: string;
  descripcion: string;
  precio: number;
  editable: boolean;
  activo: boolean;
};

type Props = {
  updateAction: string;
  syncAction: string;
  csrf: string;
  catalogo: Servicio[];
  condicionesPdf: string;
  canEditPdfCondiciones: boolean;
  success: string | null;
  error: string | null;
};

export default function App() {
  const props = readPageProps<Props>() || {
    updateAction: '/admin/catalogo-sersop',
    syncAction: '/admin/catalogo-sersop/sync-precios-sin-iva',
    csrf: csrfToken(),
    catalogo: [],
    condicionesPdf: '',
    canEditPdfCondiciones: false,
    success: null,
    error: null,
  };
  const [rows, setRows] = useState<Servicio[]>(props.catalogo);

  const addRow = () => {
    setRows((list) => [...list, { clave: '', descripcion: '', precio: 0, editable: true, activo: true }]);
  };

  return (
    <>
      <div className="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-blue-700 sm:text-3xl">Catálogo SERSOP</h1>
          <p className="mt-2 text-sm text-gray-600">
            Edita claves, descripciones y
            {' '}
            <strong>PRECIOS SIN IVA</strong>
            {' '}
            usados en la orden de servicio.
          </p>
        </div>
        <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
          <button type="button" onClick={addRow} className="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-700 px-4 py-3 text-sm font-bold text-white shadow hover:bg-blue-800">
            <i className="fas fa-plus" />
            Agregar clave
          </button>
          <form
            action={props.syncAction}
            method="POST"
            className="inline"
            onSubmit={(e) => {
              if (!window.confirm('Esto reemplaza el catálogo con los PRECIOS SIN IVA del archivo de configuración. ¿Continuar?')) {
                e.preventDefault();
              }
            }}
          >
            <input type="hidden" name="_token" value={props.csrf} />
            <button type="submit" className="inline-flex w-full items-center justify-center gap-2 rounded-lg border-2 border-emerald-600 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800 shadow hover:bg-emerald-100 sm:w-auto">
              <i className="fas fa-sync-alt" />
              Aplicar precios SIN IVA
            </button>
          </form>
        </div>
      </div>
      {props.success ? <div className="mb-5 rounded-lg bg-green-500 p-4 text-white">{props.success}</div> : null}
      {props.error ? <div className="mb-5 rounded-lg bg-red-500 p-4 text-white">{props.error}</div> : null}

      <form action={props.updateAction} method="POST" className="space-y-5">
        <input type="hidden" name="_token" value={props.csrf} />
        <div className="overflow-x-auto rounded-lg border border-blue-100">
          <table className="w-full min-w-[920px] border-collapse text-sm">
            <thead>
              <tr className="bg-blue-600 text-white">
                <th className="border p-3 text-left">CLAVE</th>
                <th className="border p-3 text-left">DESCRIPCION</th>
                <th className="border p-3 text-left">PRECIO SIN IVA</th>
                <th className="border p-3 text-center">EDITABLE EN ORDEN</th>
                <th className="border p-3 text-center">ACTIVO</th>
                <th className="border p-3 text-center">QUITAR</th>
              </tr>
            </thead>
            <tbody>
              {rows.map((row, i) => (
                <tr key={i} className="hover:bg-blue-50">
                  <td className="border p-3">
                    <input name={`servicios[${i}][clave]`} value={row.clave} onChange={(e) => setRows((list) => list.map((r, idx) => (idx === i ? { ...r, clave: e.target.value.toUpperCase() } : r)))} className="w-full rounded border border-blue-300 px-2 py-1 font-semibold uppercase text-blue-900" />
                  </td>
                  <td className="border p-3">
                    <input name={`servicios[${i}][descripcion]`} value={row.descripcion} onChange={(e) => setRows((list) => list.map((r, idx) => (idx === i ? { ...r, descripcion: e.target.value } : r)))} className="w-full rounded border border-blue-300 px-2 py-1" />
                  </td>
                  <td className="border p-3">
                    <input type="number" step="0.01" min="0" name={`servicios[${i}][precio]`} value={row.precio} onChange={(e) => setRows((list) => list.map((r, idx) => (idx === i ? { ...r, precio: Number(e.target.value) || 0 } : r)))} className="w-full rounded border border-blue-300 px-2 py-1" />
                  </td>
                  <td className="border p-3 text-center">
                    <input type="checkbox" name={`servicios[${i}][editable]`} checked={row.editable} onChange={(e) => setRows((list) => list.map((r, idx) => (idx === i ? { ...r, editable: e.target.checked } : r)))} className="h-5 w-5" />
                  </td>
                  <td className="border p-3 text-center">
                    <input type="checkbox" name={`servicios[${i}][activo]`} checked={row.activo} onChange={(e) => setRows((list) => list.map((r, idx) => (idx === i ? { ...r, activo: e.target.checked } : r)))} className="h-5 w-5" />
                  </td>
                  <td className="border p-3 text-center">
                    <button type="button" className="font-bold text-red-600 hover:text-red-800" onClick={() => setRows((list) => list.filter((_, idx) => idx !== i))}>
                      <i className="fas fa-trash" />
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        {props.canEditPdfCondiciones ? (
          <div className="rounded-lg border border-blue-200 bg-blue-50/80 p-4 sm:p-6">
            <h2 className="mb-2 text-lg font-bold text-blue-900">Condiciones de entrega del equipo (PDF)</h2>
            <textarea name="condiciones_pdf" rows={14} defaultValue={props.condicionesPdf} className="w-full rounded-lg border-2 border-blue-300 bg-white p-3 font-mono text-sm text-slate-900 shadow-inner focus:border-blue-600 focus:outline-none" />
          </div>
        ) : null}
        <button type="submit" className="w-full rounded-lg bg-gradient-to-r from-blue-500 to-blue-700 px-4 py-3 font-bold text-white shadow hover:shadow-lg">
          {props.canEditPdfCondiciones ? 'Guardar catálogo y condiciones del PDF' : 'Guardar catálogo'}
        </button>
      </form>
    </>
  );
}
