import type { ComponentType } from 'react';
import { IconCheck, IconClipboard, IconPackage, IconWrench } from './icons';
import { normalizeStatus, statusLabel } from './status';

export function statusBadgeClass(estatus: string | null | undefined): string {
  const normalized = normalizeStatus(estatus);
  if (normalized.includes('recepcion')) return 'bg-rose-100 text-rose-950';
  if (normalized.includes('proceso')) return 'bg-orange-100 text-orange-950';
  if (normalized.includes('terminado')) return 'bg-amber-100 text-amber-950';
  if (normalized.includes('entregado')) return 'bg-emerald-100 text-emerald-950';
  return 'bg-slate-100 text-slate-800';
}

function StatusIcon({ estatus }: { estatus?: string | null }) {
  const normalized = normalizeStatus(estatus);
  const Icon: ComponentType<{ size?: number }> = normalized.includes('proceso')
    ? IconWrench
    : normalized.includes('terminado')
      ? IconCheck
      : normalized.includes('entregado')
        ? IconPackage
        : IconClipboard;
  return <Icon size={16} />;
}

type Props = {
  estatus?: string | null;
  label?: string | null;
  className?: string;
};

export function StatusBadge({ estatus, label, className = '' }: Props) {
  const text = label || statusLabel(estatus);
  return (
    <span
      className={`inline-flex max-w-full min-w-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ${statusBadgeClass(estatus)} ${className}`}
    >
      <span className="anlux-icon-box h-4 w-4">
        <StatusIcon estatus={estatus} />
      </span>
      <span className="min-w-0 truncate whitespace-nowrap">{text}</span>
    </span>
  );
}
