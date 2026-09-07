import { normalizeStatus, statusColor } from '../../shared/status';

type Props = {
  id: string;
  value: string;
  options: string[];
  disabled?: boolean;
  className?: string;
  onChange: (value: string) => void;
};

function optionColors(estatus: string): { backgroundColor: string; color: string } {
  const n = normalizeStatus(estatus);
  if (n.includes('recepcion')) return { backgroundColor: '#ef4444', color: '#ffffff' };
  if (n.includes('proceso')) return { backgroundColor: '#f97316', color: '#ffffff' };
  if (n.includes('terminado')) return { backgroundColor: '#eab308', color: '#111827' };
  if (n.includes('entregado')) return { backgroundColor: '#22c55e', color: '#ffffff' };
  return { backgroundColor: '#ffffff', color: '#1e3a8a' };
}

/** Select de estatus con fondo coloreado (recepción rojo, proceso naranja, terminado amarillo, entregado verde). */
export default function EstatusSelect({ id, value, options, disabled, className = '', onChange }: Props) {
  const colors = optionColors(value);
  const tone = statusColor(value);

  return (
    <select
      id={id}
      value={value}
      disabled={disabled}
      onChange={(e) => onChange(e.target.value)}
      className={[
        'w-full rounded-lg border-2 px-3 py-3 text-base font-bold uppercase shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-1',
        tone,
        disabled ? 'cursor-not-allowed opacity-80' : '',
        className,
      ].filter(Boolean).join(' ')}
      style={{
        backgroundColor: colors.backgroundColor,
        color: colors.color,
        borderColor: colors.backgroundColor,
      }}
    >
      {options.map((opt) => {
        const c = optionColors(opt);
        return (
          <option
            key={opt}
            value={opt}
            style={{ backgroundColor: c.backgroundColor, color: c.color }}
          >
            {opt.toUpperCase()}
          </option>
        );
      })}
    </select>
  );
}
