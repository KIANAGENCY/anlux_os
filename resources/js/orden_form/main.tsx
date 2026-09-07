import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { readPageProps } from '../shared/http';
import App from './App';
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

// Paridad opcional si queda JS legacy en la misma pagina.
window.ANLUX_REACT_IVA = {
  ANLUX_IVA_RATE,
  anluxRound2,
  anluxMontoConIva,
  anluxMontoSinIvaDesdeTotal,
};

const el = document.getElementById('orden-react-root');
if (el) {
  const bootstrap = readPageProps<OrdenFormBootstrap>('react-page-props');
  createRoot(el).render(
    <StrictMode>
      <App bootstrap={bootstrap} />
    </StrictMode>,
  );
} else {
  console.warn('[anlux] #orden-react-root no encontrado; formulario React no montado.');
}
