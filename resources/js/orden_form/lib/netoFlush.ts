/** Registro de flush para inputs neto c/IVA al guardar. */

const flushers = new Set<() => void>();

export function registerNetoFlusher(fn: () => void): () => void {
  flushers.add(fn);
  return () => {
    flushers.delete(fn);
  };
}

export function flushAllNetoInputs(): void {
  flushers.forEach((fn) => {
    try {
      fn();
    } catch {
      /* ignore */
    }
  });
}
