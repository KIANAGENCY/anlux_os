import type { OrdenListItem } from './types';

function partesLogOrden(orden: OrdenListItem): string[] {
  const logRaw = String(orden.tecnicos_log ?? orden.TECNICOS_LOG ?? '').trim();
  if (!logRaw) return [];
  return logRaw.split(' · ').map((p) => p.trim()).filter(Boolean);
}

function sinDupesConsecutivos(arr: string[]): string[] {
  const out: string[] = [];
  for (const x of arr) {
    if (!x) continue;
    if (out.length === 0 || out[out.length - 1] !== x) out.push(x);
  }
  return out;
}

export function primerTecnicoOrden(orden: OrdenListItem): string {
  const tr = String(orden.tecnico_recibido || '').trim();
  if (tr) return tr;
  const p = sinDupesConsecutivos(partesLogOrden(orden));
  return p[0] || '';
}

export function secuenciaInvolucradosOrden(orden: OrdenListItem): string[] {
  const desdeApi = String(orden.involucrados_display ?? '').trim();
  if (desdeApi) {
    return desdeApi
      .split(/\r?\n| → | \u2192 /)
      .map((linea) => linea.replace(/^\d+\.\s*/, '').trim())
      .filter(Boolean);
  }
  const nombres: string[] = [];
  const push = (nombre: unknown) => {
    const n = String(nombre || '').trim();
    if (!n) return;
    if (nombres.some((ya) => ya.toLowerCase() === n.toLowerCase())) return;
    nombres.push(n);
  };
  push(orden.tecnico_recibido);
  sinDupesConsecutivos(partesLogOrden(orden)).forEach(push);
  push(orden.entregado_por_tecnico);
  return nombres;
}

export function dateOnly(value: string | null | undefined): string {
  if (!value) return '—';
  return String(value).split(' ')[0] || '—';
}

export function dateTimeShort(value: string | null | undefined): string {
  if (!value) return '—';
  return String(value).replace('T', ' ').slice(0, 16) || '—';
}

export function asBool(value: unknown): boolean {
  return value === true || value === 1 || value === '1';
}
