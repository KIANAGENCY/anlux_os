import type {
  AnticipoForm,
  ClienteState,
  EquipoForm,
  MaterialForm,
  OrdenModo,
  TrabajoForm,
} from '../types';

export const ANLUX_BORRADOR_KEY_PREFIX = 'anlux_orden_borrador_v3:';
export const ANLUX_BORRADOR_TTL_MS = 7 * 24 * 60 * 60 * 1000;

export type OrdenDraftV3 = {
  version: 3;
  savedAt: number;
  id_orden_c: string;
  cab: {
    nombre_cliente: string;
    direccion: string;
    telefono: string;
    correo: string;
    poblacion: string;
    folio: string;
    fecha_entrada: string;
    estatus: string;
  };
  equipos: Array<{
    marca: string;
    modelo: string;
    serie: string;
    descripcion_falla: string;
    tipo_servicio: string;
  }>;
  trabajos: TrabajoForm[];
  materiales: Array<{
    vale: string;
    codigo: string;
    cantidad: string;
    descripcion: string;
    precio_unitario: string;
    ticket: string;
    id_equipo: string;
  }>;
  anticipos: AnticipoForm[];
  observaciones_items: string[];
  abono_saldo: string;
  abono_saldo_equipos: Record<string, number>;
  saldo_pagado_confirmado: string;
  comentarios_tecnico: string;
  sersop01: Partial<TrabajoForm> | null;
  firmas: {
    firma_c_e: string;
    firma_t_r: string;
    firma_c_r: string;
    firma_t_e: string;
  };
};

export function borradorStorageSuffix(idOrden: number, modo: OrdenModo): string {
  if (idOrden > 0) return `edit:${idOrden}`;
  return `nueva:${modo === 'completar' ? 'completar' : 'registro'}`;
}

export function borradorStorageKey(idOrden: number, modo: OrdenModo): string {
  return ANLUX_BORRADOR_KEY_PREFIX + borradorStorageSuffix(idOrden, modo);
}

export function borradorTieneContenidoUtil(draft: OrdenDraftV3 | null | undefined): boolean {
  if (!draft || !draft.cab) return false;
  const cab = draft.cab;
  const textoCab = [cab.nombre_cliente, cab.direccion, cab.telefono, cab.correo, cab.poblacion]
    .map((v) => String(v || '').trim())
    .join('');
  if (textoCab !== '') return true;
  if ((draft.equipos || []).some((e) => String(e.marca || e.modelo || e.serie || e.descripcion_falla || '').trim() !== '')) {
    return true;
  }
  if ((draft.trabajos || []).some((t) => String(t.clave || t.descripcion || t.importe || '').trim() !== '')) {
    return true;
  }
  if ((draft.materiales || []).some((m) => String(m.vale || m.descripcion || m.precio_unitario || '').trim() !== '')) {
    return true;
  }
  if ((draft.anticipos || []).some((a) => String(a.folio || a.descripcion || a.monto || '').trim() !== '')) {
    return true;
  }
  if ((draft.observaciones_items || []).some((o) => String(o || '').trim() !== '')) return true;
  if (String(draft.comentarios_tecnico || '').trim() !== '') return true;
  if (draft.sersop01 && String(draft.sersop01.clave || '').trim() !== '') return true;
  const firmas = draft.firmas || { firma_c_e: '', firma_t_r: '', firma_c_r: '', firma_t_e: '' };
  if (firmas.firma_c_e || firmas.firma_t_r || firmas.firma_c_r || firmas.firma_t_e) return true;
  return false;
}

export function leerBorrador(key: string): OrdenDraftV3 | null {
  try {
    const raw = localStorage.getItem(key);
    if (!raw) return null;
    const parsed = JSON.parse(raw) as OrdenDraftV3;
    if (!parsed || parsed.version !== 3 || !parsed.savedAt) return null;
    if (Date.now() - Number(parsed.savedAt) > ANLUX_BORRADOR_TTL_MS) {
      localStorage.removeItem(key);
      return null;
    }
    return parsed;
  } catch {
    return null;
  }
}

export function borrarBorrador(key: string): void {
  try {
    localStorage.removeItem(key);
  } catch {
    /* ignore */
  }
}

export function guardarBorrador(key: string, draft: OrdenDraftV3): boolean {
  try {
    localStorage.setItem(key, JSON.stringify(draft));
    return true;
  } catch {
    return false;
  }
}

export function recolectarBorrador(input: {
  idOrden: number;
  cliente: ClienteState;
  equipos: EquipoForm[];
  trabajos: TrabajoForm[];
  materiales: MaterialForm[];
  anticipos: AnticipoForm[];
  observaciones: string[];
  abonoSaldo: number;
  abonoSaldoEquipos: Record<string, number>;
  saldoPagadoConfirmado: boolean;
  comentariosTecnico: string;
  sersop01: TrabajoForm | null;
  firmas: OrdenDraftV3['firmas'];
}): OrdenDraftV3 {
  return {
    version: 3,
    savedAt: Date.now(),
    id_orden_c: String(input.idOrden || 0),
    cab: {
      nombre_cliente: input.cliente.nombreCliente,
      direccion: input.cliente.direccion,
      telefono: input.cliente.telefono,
      correo: input.cliente.correo,
      poblacion: input.cliente.poblacion,
      folio: input.cliente.folio,
      fecha_entrada: input.cliente.fechaEntrada,
      estatus: input.cliente.estatus,
    },
    equipos: input.equipos.map((eq) => ({
      marca: eq.marca,
      modelo: eq.modelo,
      serie: eq.serie,
      descripcion_falla: eq.descripcionFalla,
      tipo_servicio: eq.tipoServicio,
    })),
    trabajos: input.trabajos.filter((t) => String(t.clave || '').toUpperCase() !== 'SERSOP01' || !String(t.ticket || '').includes('AUTO')),
    materiales: input.materiales.map((m) => ({
      vale: m.vale,
      codigo: m.codigo,
      cantidad: m.cant,
      descripcion: m.descripcion,
      precio_unitario: m.precio,
      ticket: m.ticket,
      id_equipo: m.id_equipo,
    })),
    anticipos: input.anticipos,
    observaciones_items: input.observaciones,
    abono_saldo: String(input.abonoSaldo || 0),
    abono_saldo_equipos: input.abonoSaldoEquipos || {},
    saldo_pagado_confirmado: input.saldoPagadoConfirmado ? '1' : '0',
    comentarios_tecnico: input.comentariosTecnico || '',
    sersop01: input.sersop01,
    firmas: input.firmas,
  };
}

export function formatHoraBorrador(savedAt: number): string {
  const d = new Date(savedAt);
  const pad = (n: number) => String(n).padStart(2, '0');
  return `${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

export function formatFechaBorrador(savedAt: number): string {
  try {
    return new Date(savedAt).toLocaleString('es-MX');
  } catch {
    return new Date(savedAt).toISOString();
  }
}
