/**
 * Snippet legacy de referencia (IVA) — no ejecutar; solo para traducir.
 * Origen: public/legacy/assets/js/orden_servicio.js
 *
 * const ANLUX_IVA_RATE = 0.16;
 * function anluxRound2(n) { return Math.round((Number(n) + Number.EPSILON) * 100) / 100; }
 * function anluxMontoConIva(montoSinIva) { return anluxRound2((Number(montoSinIva) || 0) * (1 + ANLUX_IVA_RATE)); }
 * function anluxMontoSinIvaDesdeTotal(montoConIva) {
 *   const p = Number(montoConIva) || 0;
 *   if (p <= 0) return 0;
 *   return anluxRound2(p / (1 + ANLUX_IVA_RATE));
 * }
 *
 * Portado a: resources/js/orden_form/lib/iva.ts ✅
 */
export {};
