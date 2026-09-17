import { statusColor, statusLabel } from '../../shared/status';

type Props = {
  id: string;
  value: string;
  options: string[];
  disabled?: boolean;
  /** Estatus canon (e.g. Terminado) that cannot be selected */
  disabledOptions?: string[];
  className?: string;
  onChange: (value: string) => void;
};

function normEstatus(value: string): string {
  return String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .trim()
    .toLowerCase();
}

function optionDisabled(disabledOptions: string[] | undefined, opt: string, selectDisabled: boolean | undefined): boolean {
  if (selectDisabled) return false;
  if (!disabledOptions?.length) return false;
  const n = normEstatus(opt);
  return disabledOptions.some((d) => normEstatus(d) === n);
}

export default function EstatusSelect({
  id,
  value,
  options,
  disabled,
  disabledOptions,
  className = '',
  onChange,
}: Props) {
  const tone = statusColor(value);

  return (
    <select
      id={id}
      value={value}
      disabled={disabled}
      onChange={(e) => onChange(e.target.value)}
      className={[
        'w-full min-h-11 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-blue-200',
        tone,
        disabled ? 'cursor-not-allowed opacity-80' : '',
        className,
      ].filter(Boolean).join(' ')}
      aria-label="Estatus de la orden"
    >
      {options.map((opt) => (
        <option key={opt} value={opt} disabled={optionDisabled(disabledOptions, opt, disabled)}>
          {statusLabel(opt)}
        </option>
      ))}
    </select>
  );
}
