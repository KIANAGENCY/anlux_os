import { useEffect, useRef, type RefObject } from 'react';
import SignaturePad from './SignaturePad';
import type { SignaturePadHandle } from '../types';

type Props = {
  showInicial: boolean;
  showEntrega: boolean;
  disabled: boolean;
  firmasDeshabilitadas: boolean;
  onReactivarFirmas?: () => void;
  onDirty?: () => void;
  tituloEntrega?: string;
  labelClienteEntrega?: string;
  labelTecnicoEntrega?: string;
  firmaClienteInicialRef: RefObject<SignaturePadHandle | null>;
  firmaTecnicoInicialRef: RefObject<SignaturePadHandle | null>;
  firmaClienteRef: RefObject<SignaturePadHandle | null>;
  firmaTecnicoRef: RefObject<SignaturePadHandle | null>;
  preload?: {
    firma_c_e?: string | null;
    firma_t_r?: string | null;
    firma_c_r?: string | null;
    firma_t_e?: string | null;
  };
};

export default function FirmasSection({
  showInicial,
  showEntrega,
  disabled,
  firmasDeshabilitadas,
  onReactivarFirmas,
  onDirty,
  tituloEntrega = 'FIRMAS DE ENTREGA DEL EQUIPO',
  labelClienteEntrega = 'Cliente que recibe',
  labelTecnicoEntrega = 'Tecnico que entrega',
  firmaClienteInicialRef,
  firmaTecnicoInicialRef,
  firmaClienteRef,
  firmaTecnicoRef,
  preload,
}: Props) {
  const loadedRef = useRef(false);

  useEffect(() => {
    if (loadedRef.current || firmasDeshabilitadas || !preload) return;
    loadedRef.current = true;
    void (async () => {
      await firmaClienteInicialRef.current?.loadFromDataUrl(preload.firma_c_e);
      await firmaTecnicoInicialRef.current?.loadFromDataUrl(preload.firma_t_r);
      await firmaClienteRef.current?.loadFromDataUrl(preload.firma_c_r);
      await firmaTecnicoRef.current?.loadFromDataUrl(preload.firma_t_e);
    })();
  }, [
    firmasDeshabilitadas,
    preload,
    firmaClienteInicialRef,
    firmaTecnicoInicialRef,
    firmaClienteRef,
    firmaTecnicoRef,
  ]);

  if (firmasDeshabilitadas) {
    return (
      <section className="rounded-r-lg border border-slate-300 bg-slate-50 p-4 text-sm text-slate-700">
        <p>
          <i className="fas fa-ban mr-2 text-slate-600" />
          <strong>Firmas deshabilitadas:</strong>
          {' '}
          al guardar se envian imagenes vacias (PNG transparente).
        </p>
        {onReactivarFirmas && !disabled ? (
          <button
            type="button"
            className="mt-3 inline-flex items-center rounded-lg border-2 border-blue-600 bg-white px-4 py-2 text-sm font-bold text-blue-800 hover:bg-blue-50"
            onClick={onReactivarFirmas}
          >
            <i className="fas fa-pen-fancy mr-2" />
            Volver a llenar campos y activar firmas
          </button>
        ) : null}
      </section>
    );
  }

  return (
    <>
      {showInicial ? (
        <section className="rounded-r-lg border-l-4 border-blue-700 bg-blue-50 p-4 sm:pl-6">
          <h2 className="mb-8 flex items-center text-xl font-bold text-blue-900 sm:text-2xl">
            <i className="fas fa-pen-fancy mr-3 text-blue-700" />
            FIRMAS
          </h2>
          <div className="grid grid-cols-1 gap-8 md:grid-cols-2">
            <div className="text-center">
              <SignaturePad ref={firmaClienteInicialRef} disabled={disabled} label="Cliente" minHeight={240} onDirty={onDirty} />
            </div>
            <div className="text-center">
              <SignaturePad ref={firmaTecnicoInicialRef} disabled={disabled} label="Tecnico" minHeight={240} onDirty={onDirty} />
            </div>
          </div>
        </section>
      ) : null}

      {showEntrega ? (
        <section className="rounded-r-lg border-l-4 border-blue-700 bg-blue-50 p-4 sm:pl-6">
          <h2 className="mb-8 flex items-center text-xl font-bold text-blue-900 sm:text-2xl">
            <i className="fas fa-pen-fancy mr-3 text-blue-700" />
            {tituloEntrega}
          </h2>
          <div className="grid grid-cols-1 gap-8 md:grid-cols-2">
            <div className="text-center">
              <SignaturePad
                ref={firmaClienteRef}
                disabled={disabled}
                label={labelClienteEntrega}
                minHeight={240}
                onDirty={onDirty}
              />
            </div>
            <div className="text-center">
              <SignaturePad
                ref={firmaTecnicoRef}
                disabled={disabled}
                label={labelTecnicoEntrega}
                minHeight={240}
                onDirty={onDirty}
              />
            </div>
          </div>
        </section>
      ) : null}
    </>
  );
}
