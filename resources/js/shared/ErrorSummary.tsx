import { useEffect, useRef } from 'react';
import { IconWarning } from './icons';

export type FormFieldError = {
  id: string;
  message: string;
  focus?: string;
};

type Props = {
  errors: FormFieldError[];
  title?: string;
  onJump?: (focus?: string) => void;
};

export function ErrorSummary({ errors, title = 'Hay un problema', onJump }: Props) {
  const ref = useRef<HTMLDivElement>(null);
  const prevErrorCountRef = useRef(0);

  // Solo enfocar el resumen al aparecer errores (p. ej. Guardar), no al corregir un campo.
  useEffect(() => {
    const prev = prevErrorCountRef.current;
    if (errors.length > 0 && prev === 0) {
      ref.current?.focus();
    }
    prevErrorCountRef.current = errors.length;
  }, [errors]);

  if (errors.length === 0) return null;

  return (
    <div
      ref={ref}
      role="alert"
      tabIndex={-1}
      aria-labelledby="anlux-error-title"
      className="anlux-error-summary"
    >
      <h2 id="anlux-error-title" className="flex items-center gap-2 text-sm font-bold">
        <span className="anlux-icon-box">
          <IconWarning size={20} />
        </span>
        {title}
      </h2>
      <ul className="mt-2 list-disc space-y-1 pl-5">
        {errors.map((error) => (
          <li key={error.id}>
            {error.focus ? (
              <a
                href={`#${error.focus}`}
                onClick={(event) => {
                  event.preventDefault();
                  onJump?.(error.focus);
                }}
              >
                {error.message}
              </a>
            ) : (
              error.message
            )}
          </li>
        ))}
      </ul>
    </div>
  );
}
