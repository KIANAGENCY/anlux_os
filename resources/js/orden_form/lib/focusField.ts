/** Enfoca el primer elemento con data-anlux-field (paridad Exacto focus). */
export function focusAnluxField(field: string | undefined | null): void {
  if (!field || typeof document === 'undefined') return;
  const el = document.querySelector(`[data-anlux-field="${CSS.escape(field)}"]`) as HTMLElement | null;
  if (!el) return;
  try {
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
  } catch {
    el.scrollIntoView();
  }
  window.setTimeout(() => {
    try {
      el.focus();
      if (el instanceof HTMLInputElement || el instanceof HTMLTextAreaElement) {
        el.select?.();
      }
    } catch {
      /* ignore */
    }
  }, 50);
}
