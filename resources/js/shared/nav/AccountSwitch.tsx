import type { AccountStatus, ImpersonationApiAccount } from './types';

const STATUS_PREFIX: Record<AccountStatus, string> = {
  online: '● ',
  in_use: '◐ ',
  offline: '○ ',
  inactive: '✕ ',
  self: '→ ',
};

const OPTION_CLASS: Record<AccountStatus, string> = {
  online: 'anlux-opt-online',
  in_use: 'anlux-opt-in_use',
  offline: 'anlux-opt-offline',
  inactive: 'anlux-opt-inactive',
  self: 'anlux-opt-self',
};

export function accountHint(status: AccountStatus): string {
  if (status === 'inactive') return 'La cuenta está desactivada.';
  if (status === 'in_use') return 'Acceso directo disponible; vuelve a intentar.';
  if (status === 'self') return 'Ya estás en esta cuenta.';
  if (status === 'online') return 'En línea: acceso directo.';
  if (status === 'offline') return 'Sin conexión: entrarás de inmediato.';
  return 'No disponible.';
}

export function enrichAccounts(accounts: ImpersonationApiAccount[]): ImpersonationApiAccount[] {
  return accounts.map((acc) => ({
    ...acc,
    hint: acc.selectable ? undefined : accountHint(acc.status),
  }));
}

type AccountSwitchProps = {
  accounts: ImpersonationApiAccount[];
  userId: number;
  value: string;
  disabled?: boolean;
  compact?: boolean;
  onChange: (targetId: number, account: ImpersonationApiAccount | null) => void;
};

export function AccountSwitch({
  accounts,
  userId,
  value,
  disabled,
  compact = true,
  onChange,
}: AccountSwitchProps) {
  return (
    <div className={compact ? 'anlux-account-switch-wrap shrink-0' : 'anlux-account-switch-wrap w-full md:w-auto md:min-w-[260px]'}>
      <label className="sr-only" htmlFor="navSelectCuenta">
        Cambiar de cuenta
      </label>
      <select
        id="navSelectCuenta"
        title="● En línea · ◐ En uso · ○ Sin conexión · ✕ Inactiva"
        className={
          compact
            ? 'anlux-account-switch h-9 max-w-[200px] rounded-lg border border-slate-300 bg-white px-2.5 text-xs font-semibold text-blue-900 sm:max-w-[240px] sm:text-sm'
            : 'anlux-account-switch anlux-control w-full min-w-[260px] font-semibold text-blue-900'
        }
        data-current-id={userId}
        value={value}
        disabled={disabled}
        onChange={(e) => {
          const targetId = Number(e.target.value || 0);
          if (!targetId) return;
          const account = accounts.find((a) => a.id === targetId) ?? null;
          onChange(targetId, account);
        }}
      >
        <option value="">— Cambiar de cuenta —</option>
        {accounts.map((acc) => {
          const status = acc.status;
          const label = acc.status_label ? ` — ${acc.status_label}` : '';
          return (
            <option
              key={acc.id}
              value={String(acc.id)}
              className={OPTION_CLASS[status] ?? ''}
              data-status={status}
              data-hint={acc.hint || ''}
              disabled={!acc.selectable}
            >
              {`${STATUS_PREFIX[status] ?? ''}${acc.nombre}${label}`}
            </option>
          );
        })}
      </select>
      {!compact ? (
        <div
          className="anlux-account-switch-legend mt-1 flex flex-nowrap gap-x-3 overflow-x-auto whitespace-nowrap text-[11px] font-medium text-slate-600"
          aria-hidden="true"
        >
          <span>
            <span className="anlux-dot anlux-dot--online" />
            {' '}
            En línea
          </span>
          <span>
            <span className="anlux-dot anlux-dot--in_use" />
            {' '}
            En uso
          </span>
          <span>
            <span className="anlux-dot anlux-dot--offline" />
            {' '}
            Sin conexión
          </span>
          <span>
            <span className="anlux-dot anlux-dot--inactive" />
            {' '}
            Inactiva
          </span>
        </div>
      ) : null}
    </div>
  );
}

export function TechnicianName({ nombre }: { nombre: string }) {
  if (!nombre) return null;
  return (
    <p className="mb-0 flex min-h-11 shrink-0 items-center text-sm font-bold text-slate-800 sm:text-right">
      <span className="font-semibold text-slate-600">Técnico:</span>
      <span className="text-blue-600">
        <i className="fas fa-user-circle ml-1 mr-1" aria-hidden="true" />
      </span>
      {nombre}
    </p>
  );
}

export function ImpersonationBanner({
  nombreTecnico,
  onExit,
}: {
  nombreTecnico: string;
  onExit: () => void;
}) {
  return (
    <div className="w-full rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-900 sm:w-auto">
      <i className="fas fa-user-secret mr-1" aria-hidden="true" />
      Actuando como:
      {' '}
      <strong>{nombreTecnico}</strong>
      <button
        type="button"
        id="navBtnSalirImpersonacion"
        className="ml-2 inline-flex items-center rounded border border-amber-500 bg-white px-2 py-0.5 text-xs font-bold text-amber-800 hover:bg-amber-100"
        onClick={onExit}
      >
        Volver a mi cuenta
      </button>
    </div>
  );
}
