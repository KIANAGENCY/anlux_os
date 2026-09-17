import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { readPageProps } from '../shared/http';
import App from './App';
import { OrdenFormErrorBoundary } from './OrdenFormErrorBoundary';
import type { OrdenFormBootstrap } from './types';
import { ANLUX_IVA_RATE, anluxMontoConIva, anluxMontoSinIvaDesdeTotal, anluxRound2 } from './lib/iva';

declare global {
  interface Window {
    ANLUX_REACT_IVA?: {
      ANLUX_IVA_RATE: number;
      anluxRound2: typeof anluxRound2;
      anluxMontoConIva: typeof anluxMontoConIva;
      anluxMontoSinIvaDesdeTotal: typeof anluxMontoSinIvaDesdeTotal;
    };
  }
}

window.ANLUX_REACT_IVA = {
  ANLUX_IVA_RATE,
  anluxRound2,
  anluxMontoConIva,
  anluxMontoSinIvaDesdeTotal,
};

function mountOrdenForm(): void {
  const el = document.getElementById('orden-react-root');
  if (!el) {
    console.warn('[anlux] #orden-react-root no encontrado; formulario React no montado.');
    return;
  }

  const bootstrap = readPageProps<OrdenFormBootstrap>('react-page-props');
  try {
    createRoot(el).render(
      <StrictMode>
        <OrdenFormErrorBoundary>
          <App bootstrap={bootstrap} />
        </OrdenFormErrorBoundary>
      </StrictMode>,
    );
  } catch (err) {
    const msg = err instanceof Error ? err.message : String(err);
    console.error('[anlux] mountOrdenForm:', err);
    el.innerHTML = `<div class="rounded-lg border-2 border-red-300 bg-red-50 p-6 text-red-900"><p class="font-bold">Error al iniciar el formulario</p><p class="mt-2 text-sm">${msg}</p></div>`;
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', mountOrdenForm);
} else {
  mountOrdenForm();
}
