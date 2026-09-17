import type { FormFieldError } from '../../shared/ErrorSummary';
import type { AlertOptions } from '../../shared/nav/ui';

type ShowAlert = (
  message: string,
  options?: AlertOptions,
) => Promise<void>;

function textoErrores(errors: FormFieldError[]): string {
  if (errors.length === 0) return '';
  if (errors.length === 1) return errors[0].message;
  return `Corrige los siguientes campos:\n\n${errors.map((e) => `• ${e.message}`).join('\n')}`;
}

/** Modal Anlux (#anluxUiModal) como Exacto; fallback React DialogProvider. */
export async function alertErroresOrden(showAlert: ShowAlert, errors: FormFieldError[]): Promise<void> {
  if (errors.length === 0) return;
  const texto = textoErrores(errors);
  const options = { title: 'Faltan datos en la orden', icon: 'warning' as const };

  if (typeof window.anluxShowAlert === 'function') {
    try {
      await window.anluxShowAlert(texto, options);
      return;
    } catch {
      /* fallback abajo */
    }
  }
  try {
    await showAlert(texto, options);
  } catch {
    window.alert(`${options.title}\n\n${texto}`);
  }
}

export async function alertCampoRequerido(
  showAlert: ShowAlert,
  etiqueta: string,
  focus: string,
): Promise<void> {
  const message = `${etiqueta}: este campo es obligatorio.`;
  await alertErroresOrden(showAlert, [{ id: focus, message, focus }]);
}
