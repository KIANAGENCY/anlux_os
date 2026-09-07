/**
 * Port exacto de la lógica IVA de orden_servicio.js (mantener paridad).
 * Fuente: public/legacy/assets/js/orden_servicio.js
 */
export const ANLUX_IVA_RATE = 0.16;

export function anluxRound2(n: number): number {
  return Math.round((Number(n) + Number.EPSILON) * 100) / 100;
}

export function anluxMontoConIva(montoSinIva: number): number {
  return anluxRound2((Number(montoSinIva) || 0) * (1 + ANLUX_IVA_RATE));
}

export function anluxMontoSinIvaDesdeTotal(montoConIva: number): number {
  const p = Number(montoConIva) || 0;
  if (p <= 0) return 0;
  return anluxRound2(p / (1 + ANLUX_IVA_RATE));
}

export type OrdenTotales = {
  subtotalTrabajos: number;
  subtotalMateriales: number;
  subtotalCombinado: number;
  ivaTotal: number;
  total: number;
  anticiposSinIva: number;
  abonoSaldo: number;
  saldoPendiente: number;
};

/** Totales de orden: trabajos/materiales sin IVA + anticipos/abono. */
export function computeOrdenTotales(input: {
  trabajosImportes: number[];
  materiales: Array<{ cant: number; precio: number }>;
  anticiposMontos: number[];
  abonoSaldo: number;
}): OrdenTotales {
  const subtotalTrabajos = anluxRound2(
    input.trabajosImportes.reduce((acc, n) => acc + (Number(n) || 0), 0),
  );
  const subtotalMateriales = anluxRound2(
    input.materiales.reduce((acc, m) => {
      const cant = Number(m.cant) || 0;
      const precio = Number(m.precio) || 0;
      return acc + cant * precio;
    }, 0),
  );
  const subtotalCombinado = anluxRound2(subtotalTrabajos + subtotalMateriales);
  const ivaTotal = anluxRound2(subtotalCombinado * ANLUX_IVA_RATE);
  const total = anluxRound2(subtotalCombinado + ivaTotal);
  const anticiposSinIva = anluxRound2(
    input.anticiposMontos.reduce((acc, n) => acc + (Number(n) || 0), 0),
  );
  let abonoSaldo = Math.max(0, Number(input.abonoSaldo) || 0);
  const pagosSinIva = anticiposSinIva + abonoSaldo;
  let saldoPendiente = anluxRound2(total - anluxMontoConIva(pagosSinIva));

  // Residuo ±$0.01 por ida-vuelta de IVA al liquidar (paridad con legacy).
  if (Math.abs(saldoPendiente) > 0.004 && Math.abs(saldoPendiente) <= 0.011 && pagosSinIva > 0.009) {
    abonoSaldo = Math.max(0, total / (1 + ANLUX_IVA_RATE) - anticiposSinIva);
    saldoPendiente = anluxRound2(total - anluxMontoConIva(anticiposSinIva + abonoSaldo));
  }

  return {
    subtotalTrabajos,
    subtotalMateriales,
    subtotalCombinado,
    ivaTotal,
    total,
    anticiposSinIva,
    abonoSaldo,
    saldoPendiente,
  };
}

/** Abono sin IVA necesario para dejar saldo pendiente en ~0. */
export function abonoParaLiquidarSaldo(totalConIva: number, anticiposSinIva: number): number {
  const needed = totalConIva / (1 + ANLUX_IVA_RATE) - (Number(anticiposSinIva) || 0);
  return anluxRound2(Math.max(0, needed));
}

/** Índice 1-based de equipo en filas de cargos (vacío → 1), paridad Exacto. */
export function idEquipoFilaLiquidar(idEquipo: string | number | null | undefined): number {
  return Number(idEquipo) || 1;
}

/**
 * Saldo pendiente con IVA de un equipo (trabajos+materiales − anticipos − abono mapa).
 * Índices de mapa: "1","2",… (1-based).
 */
export function calcularSaldoEquipo(input: {
  numEquipo: number;
  trabajos: Array<{ importe: string | number; id_equipo: string }>;
  materiales: Array<{ cant: string | number; precio: string | number; id_equipo: string }>;
  anticipos: Array<{ monto: string | number; id_equipo: string }>;
  abonoMap: Record<string, number>;
}): number {
  const num = Number(input.numEquipo) || 0;
  if (num <= 0) return 0;

  let trabajos = 0;
  for (const t of input.trabajos) {
    if (idEquipoFilaLiquidar(t.id_equipo) !== num) continue;
    trabajos += Number(t.importe) || 0;
  }
  let materiales = 0;
  for (const m of input.materiales) {
    if (idEquipoFilaLiquidar(m.id_equipo) !== num) continue;
    materiales += (Number(m.cant) || 0) * (Number(m.precio) || 0);
  }
  let anticipos = 0;
  for (const a of input.anticipos) {
    if (idEquipoFilaLiquidar(a.id_equipo) !== num) continue;
    anticipos += Number(a.monto) || 0;
  }
  const yaLiquidado = Number(input.abonoMap[String(num)] || input.abonoMap[num as unknown as string] || 0) || 0;
  const saldoSinIva = Math.max(0, anluxRound2(trabajos + materiales - anticipos - yaLiquidado));
  return anluxMontoConIva(saldoSinIva);
}

/** Aplica liquidación parcial por equipos seleccionados (paridad exactoAplicarLiquidacionEquipos). */
export function aplicarLiquidacionEquipos(input: {
  items: Array<{ num: number; saldoConIva: number }>;
  saldoPendienteOrden: number;
  abonoSaldoActual: number;
  abonoMap: Record<string, number>;
}): { ok: boolean; abonoSaldo: number; abonoMap: Record<string, number>; aplicadoConIva: number; message?: string } {
  let restanteOrden = Number(input.saldoPendienteOrden) || 0;
  if (restanteOrden <= 0.009) {
    return {
      ok: false,
      abonoSaldo: input.abonoSaldoActual,
      abonoMap: input.abonoMap,
      aplicadoConIva: 0,
      message: 'No hay saldo pendiente por pagar.',
    };
  }

  const map = { ...input.abonoMap };
  let aAplicar = 0;
  for (const it of input.items) {
    const num = Number(it.num) || 0;
    const pedido = Math.max(0, Number(it.saldoConIva) || 0);
    const parte = anluxRound2(Math.min(pedido, restanteOrden - aAplicar));
    if (num <= 0 || parte <= 0.009) continue;
    aAplicar = anluxRound2(aAplicar + parte);
    const key = String(num);
    map[key] = anluxRound2((Number(map[key]) || 0) + anluxMontoSinIvaDesdeTotal(parte));
  }

  if (aAplicar <= 0.009) {
    return {
      ok: false,
      abonoSaldo: input.abonoSaldoActual,
      abonoMap: input.abonoMap,
      aplicadoConIva: 0,
      message: 'El equipo seleccionado no tiene saldo pendiente según sus cálculos.',
    };
  }

  return {
    ok: true,
    abonoSaldo: anluxRound2((Number(input.abonoSaldoActual) || 0) + anluxMontoSinIvaDesdeTotal(aAplicar)),
    abonoMap: map,
    aplicadoConIva: aAplicar,
  };
}
