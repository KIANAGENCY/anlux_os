/** Enfoca el primer elemento con data-anlux-field. */
export function focusAnluxField(field: string | undefined | null): void {
  if (!field || typeof document === 'undefined') return;
  const el = document.querySelector(`[data-anlux-field="${CSS.escape(field)}"]`) as HTMLElement | null;
  if (!el) return;
  const focusTarget = el.matches('input, select, textarea, button, [tabindex]')
    ? el
    : el.querySelector<HTMLElement>('input, select, textarea, button, [tabindex]') || el;
  if (focusTarget === el && !el.hasAttribute('tabindex')) {
    el.tabIndex = -1;
  }
  el.classList.remove('anlux-field-attention');
  void el.offsetWidth;
  el.classList.add('anlux-field-attention');
  try {
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    el.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' });
  } catch {
    el.scrollIntoView();
  }
  window.setTimeout(() => {
    try {
      focusTarget.focus({ preventScroll: true });
      if (focusTarget instanceof HTMLInputElement || focusTarget instanceof HTMLTextAreaElement) {
        focusTarget.select?.();
      }
    } catch {
      /* ignore */
    }
  }, 180);
  window.setTimeout(() => el.classList.remove('anlux-field-attention'), 1100);
}
