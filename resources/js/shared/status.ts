export function normalizeStatus(estatus: string | null | undefined): string {
  return String(estatus || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase();
}

export function statusLabel(estatus: string | null | undefined): string {
  const normalized = normalizeStatus(estatus);
  if (normalized.includes('recepcion')) return 'Recepción';
  if (normalized.includes('proceso')) return 'En proceso';
  if (normalized.includes('terminado')) return 'Terminado';
  if (normalized.includes('entregado')) return 'Entregado';
  return estatus || 'Desconocido';
}

export function statusColor(estatus: string | null | undefined): string {
  const normalized = normalizeStatus(estatus);
  if (normalized.includes('recepcion')) return 'bg-rose-100 text-rose-950';
  if (normalized.includes('proceso')) return 'bg-orange-100 text-orange-950';
  if (normalized.includes('terminado')) return 'bg-amber-100 text-amber-950';
  if (normalized.includes('entregado')) return 'bg-emerald-100 text-emerald-950';
  return 'bg-slate-100 text-slate-800';
}
