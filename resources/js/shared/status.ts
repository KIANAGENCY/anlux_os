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
  if (normalized.includes('recepcion')) return 'bg-red-500 text-white';
  if (normalized.includes('proceso')) return 'bg-orange-500 text-white';
  if (normalized.includes('terminado')) return 'bg-yellow-500 text-black';
  if (normalized.includes('entregado')) return 'bg-green-500 text-white';
  return 'bg-gray-400 text-white';
}
