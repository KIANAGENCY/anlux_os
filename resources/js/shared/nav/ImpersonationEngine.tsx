import { useCallback, useEffect, useRef, useState } from 'react';
import {
  aplicarImpersonacion,
  cancelarImpersonacion,
  fetchCuentas,
  fetchEstado,
  fetchPendientes,
  responderImpersonacion,
  salirImpersonacion,
  solicitarImpersonacion,
} from './api';
import {
  AccountSwitch,
  ImpersonationBanner,
  TechnicianName,
  accountHint,
  enrichAccounts,
} from './AccountSwitch';
import { LoadingOverlay, useAnluxDialog } from './ui';
import type { ImpersonationApiAccount, SwitchAccount } from './types';

const STORAGE_TOKEN_KEY = 'anlux_impersonacion_token';

type ImpersonationEngineProps = {
  isImpersonating: boolean;
  canSwitchAccount: boolean;
  userId: number;
  initialAccounts: SwitchAccount[];
  nombreTecnico: string;
  /** false = layout admin clásico (select ancho + leyenda) */
  compact?: boolean;
};

export function ImpersonationEngine({
  isImpersonating,
  canSwitchAccount,
  userId,
  initialAccounts,
  nombreTecnico,
  compact = true,
}: ImpersonationEngineProps) {
  const { showAlert, showConfirm } = useAnluxDialog();
  const [accounts, setAccounts] = useState<ImpersonationApiAccount[]>(() => enrichAccounts(initialAccounts));
  const [selectValue, setSelectValue] = useState(() => {
    const self = initialAccounts.find((a) => a.status === 'self');
    return self ? String(self.id) : '';
  });
  const [lastValue, setLastValue] = useState(selectValue);
  const [selectDisabled, setSelectDisabled] = useState(false);
  const [loading, setLoading] = useState(false);
  const [loadingMessage, setLoadingMessage] = useState('Procesando…');

  const pollRequesterTokenRef = useRef<string | null>(null);
  const pollRequesterTimerRef = useRef<number | null>(null);
  const pollInboxTimerRef = useRef<number | null>(null);
  const accountsRefreshTimerRef = useRef<number | null>(null);
  const applyingSessionRef = useRef(false);
  const handledRequestIdsRef = useRef(new Set<number>());

  const showLoading = useCallback((message: string) => {
    setLoadingMessage(message || 'Procesando…');
    setLoading(true);
    document.body.style.overflow = 'hidden';
  }, []);

  const hideLoading = useCallback(() => {
    setLoading(false);
    document.body.style.overflow = '';
  }, []);

  const updateLoadingMessage = useCallback((message: string) => {
    setLoadingMessage(message || '');
  }, []);

  const persistToken = useCallback((token: string) => {
    try {
      sessionStorage.setItem(STORAGE_TOKEN_KEY, token);
    } catch {
      /* ignore */
    }
  }, []);

  const clearPersistedToken = useCallback(() => {
    try {
      sessionStorage.removeItem(STORAGE_TOKEN_KEY);
    } catch {
      /* ignore */
    }
  }, []);

  const applySessionAndReload = useCallback(
    async (token: string): Promise<boolean> => {
      applyingSessionRef.current = true;
      updateLoadingMessage('Entrando…');
      const { res, data } = await aplicarImpersonacion(token);
      if (res.ok && data.success) {
        clearPersistedToken();
        window.location.reload();
        return true;
      }
      applyingSessionRef.current = false;
      return false;
    },
    [clearPersistedToken, updateLoadingMessage],
  );

  const loadAccountsSelectRef = useRef<(() => Promise<void>) | null>(null);

  const stopRequesterPoll = useCallback(
    async (cancelledByUser: boolean) => {
      const tokenToCancel = pollRequesterTokenRef.current;

      if (pollRequesterTimerRef.current !== null) {
        window.clearInterval(pollRequesterTimerRef.current);
        pollRequesterTimerRef.current = null;
      }
      pollRequesterTokenRef.current = null;
      applyingSessionRef.current = false;
      clearPersistedToken();
      hideLoading();

      if (tokenToCancel && cancelledByUser) {
        try {
          await cancelarImpersonacion(tokenToCancel);
        } catch {
          /* ignore */
        }
      }

      if (cancelledByUser) {
        setSelectValue(lastValue);
        void loadAccountsSelectRef.current?.();
      }
    },
    [clearPersistedToken, hideLoading, lastValue],
  );

  const startRequesterPoll = useCallback(() => {
    if (pollRequesterTimerRef.current !== null) {
      window.clearInterval(pollRequesterTimerRef.current);
      pollRequesterTimerRef.current = null;
    }
    if (!pollRequesterTokenRef.current) return;

    persistToken(pollRequesterTokenRef.current);
    pollRequesterTimerRef.current = window.setInterval(() => {
      void pollRequesterStatus();
    }, 1200);
    void pollRequesterStatus();

    async function pollRequesterStatus() {
      const token = pollRequesterTokenRef.current;
      if (!token || applyingSessionRef.current) return;

      const { res, data } = await fetchEstado(token);

      if (!res.ok || !data.success) {
        if (data.status === 'invalid' || res.status === 404) {
          await stopRequesterPoll(true);
          await showAlert(data.message || 'La solicitud ya no es válida.', {
            icon: 'warning',
            title: 'Solicitud expirada',
          });
        }
        return;
      }

      if (data.status === 'pending') {
        updateLoadingMessage('Entrando…');
        const ok = await applySessionAndReload(token);
        if (!ok) await stopRequesterPoll(true);
        return;
      }

      if (data.status === 'approved' && data.can_apply) {
        const ok = await applySessionAndReload(token);
        if (!ok) {
          await stopRequesterPoll(true);
          await showAlert('No se pudo entrar a la cuenta.', { icon: 'error', title: 'Error al acceder' });
        }
        return;
      }

      if (data.status === 'denied' || data.status === 'expired') {
        await stopRequesterPoll(true);
        setSelectValue(lastValue);
        await showAlert(data.message || 'Acceso no autorizado.', { icon: 'warning' });
      }
    }
  }, [applySessionAndReload, lastValue, persistToken, showAlert, stopRequesterPoll, updateLoadingMessage]);

  const accountsCountRef = useRef(initialAccounts.length);
  const cuentasAlertShownRef = useRef(false);

  const loadAccountsSelect = useCallback(async () => {
    if (!canSwitchAccount) return;

    const { res, data } = await fetchCuentas();
    const current = userId;
    const previous = selectValue || lastValue;

    if (!res.ok || !data.success) {
      if (accountsCountRef.current <= 1 && !cuentasAlertShownRef.current) {
        cuentasAlertShownRef.current = true;
        const msg =
          data.message ||
          (res.status === 500 ? 'Error del servidor al cargar cuentas.' : 'No se pudieron cargar las cuentas.');
        await showAlert(msg, { icon: 'warning', title: 'Selector de cuentas' });
      }
      return;
    }

    cuentasAlertShownRef.current = false;

    const list = Array.isArray(data.data) ? data.data : [];
    if (list.length === 0) return;

    const enriched = enrichAccounts(list);
    accountsCountRef.current = enriched.length;
    setAccounts(enriched);

    const selfAcc = enriched.find((t) => Number(t.id) === current && t.status === 'self');
    if (selfAcc) {
      setSelectValue(String(selfAcc.id));
      setLastValue(String(selfAcc.id));
    } else if (previous && enriched.some((t) => String(t.id) === String(previous))) {
      setSelectValue(previous);
    }
  }, [canSwitchAccount, lastValue, selectValue, showAlert, userId]);

  loadAccountsSelectRef.current = loadAccountsSelect;

  const onAccountSelectChange = useCallback(
    async (_targetId: number, account: ImpersonationApiAccount | null) => {
      const previous = lastValue;
      const targetId = account?.id ?? 0;
      if (!targetId) return;

      showLoading('Entrando…');
      setSelectDisabled(true);

      if (account && !account.selectable) {
        setSelectDisabled(false);
        setSelectValue(previous);
        hideLoading();
        await showAlert(account.hint || accountHint(account.status), {
          icon: 'warning',
          title: 'Cuenta no disponible',
        });
        return;
      }

      const { res, data } = await solicitarImpersonacion(targetId);
      setSelectDisabled(false);

      if (!res.ok || !data.success) {
        await stopRequesterPoll(true);
        setSelectValue(previous);
        await showAlert(data.message || 'No se pudo solicitar el acceso.', { icon: 'error' });
        return;
      }

      const token = data.token || '';
      pollRequesterTokenRef.current = token;
      setLastValue(String(targetId));
      persistToken(token);

      if (data.instant_apply || data.can_apply) {
        updateLoadingMessage('Entrando…');
        const ok = await applySessionAndReload(token);
        if (!ok) {
          await stopRequesterPoll(true);
          setSelectValue(previous);
          await showAlert('No se pudo entrar a la cuenta.', { icon: 'error' });
        }
        return;
      }

      updateLoadingMessage('Entrando…');
      const okFallback = await applySessionAndReload(token);
      if (!okFallback) {
        await stopRequesterPoll(true);
        setSelectValue(previous);
        await showAlert('No se pudo entrar a la cuenta.', { icon: 'error' });
      }
    },
    [
      applySessionAndReload,
      hideLoading,
      lastValue,
      persistToken,
      showAlert,
      showLoading,
      stopRequesterPoll,
      updateLoadingMessage,
    ],
  );

  const pollTargetInbox = useCallback(async () => {
    if (isImpersonating) return;

    const { res, data } = await fetchPendientes();
    if (!res.ok || !data.success || !Array.isArray(data.data)) return;

    for (const item of data.data) {
      const id = Number(item.id || 0);
      if (!id || handledRequestIdsRef.current.has(id)) continue;
      handledRequestIdsRef.current.add(id);

      const quien = item.requester_nombre || 'Un usuario';
      const mensaje = `${quien} quiere acceder a tu cuenta.\n\n¿Permites el acceso?`;
      const approve = await showConfirm(mensaje, {
        title: 'Acceso a tu cuenta',
        confirmText: 'Aceptar',
        cancelText: 'Rechazar',
        icon: 'warning',
      });

      await responderImpersonacion(id, approve);
    }
  }, [isImpersonating, showConfirm]);

  const exitImpersonation = useCallback(async () => {
    showLoading('Volviendo a tu cuenta…');
    const { res, data } = await salirImpersonacion();
    if (res.ok && data.success) {
      clearPersistedToken();
      window.location.reload();
      return;
    }
    hideLoading();
    await showAlert(data.message || 'No se pudo volver a tu cuenta.', { icon: 'error' });
  }, [clearPersistedToken, hideLoading, showAlert, showLoading]);

  const resumePendingAccessIfAny = useCallback(async () => {
    if (isImpersonating) return;

    let saved = '';
    try {
      saved = sessionStorage.getItem(STORAGE_TOKEN_KEY) || '';
    } catch {
      saved = '';
    }
    if (!saved) return;

    pollRequesterTokenRef.current = saved;
    showLoading('Entrando…');
    const ok = await applySessionAndReload(saved);
    if (!ok) await stopRequesterPoll(true);
  }, [applySessionAndReload, isImpersonating, showLoading, stopRequesterPoll]);

  useEffect(() => {
    if (isImpersonating || !canSwitchAccount) return undefined;

    void loadAccountsSelect();
    void resumePendingAccessIfAny();

    accountsRefreshTimerRef.current = window.setInterval(() => {
      if (!pollRequesterTokenRef.current && !applyingSessionRef.current) {
        void loadAccountsSelect();
      }
    }, 4000);

    return () => {
      if (accountsRefreshTimerRef.current !== null) {
        window.clearInterval(accountsRefreshTimerRef.current);
      }
    };
  }, [canSwitchAccount, isImpersonating, loadAccountsSelect, resumePendingAccessIfAny]);

  useEffect(() => {
    if (isImpersonating) return undefined;

    const pollMs = 3000;
    pollInboxTimerRef.current = window.setInterval(() => {
      void pollTargetInbox();
    }, pollMs);
    void pollTargetInbox();

    const onVisible = () => {
      if (document.visibilityState !== 'visible') return;
      void pollTargetInbox();
      if (!pollRequesterTokenRef.current) {
        void loadAccountsSelect();
      }
      if (pollRequesterTokenRef.current) {
        startRequesterPoll();
      }
    };

    const onFocus = () => {
      void pollTargetInbox();
      if (!pollRequesterTokenRef.current) {
        void loadAccountsSelect();
      }
      if (pollRequesterTokenRef.current) {
        startRequesterPoll();
      }
    };

    document.addEventListener('visibilitychange', onVisible);
    window.addEventListener('focus', onFocus);

    return () => {
      if (pollInboxTimerRef.current !== null) {
        window.clearInterval(pollInboxTimerRef.current);
      }
      if (pollRequesterTimerRef.current !== null) {
        window.clearInterval(pollRequesterTimerRef.current);
      }
      document.removeEventListener('visibilitychange', onVisible);
      window.removeEventListener('focus', onFocus);
    };
  }, [isImpersonating, loadAccountsSelect, pollTargetInbox, startRequesterPoll]);

  return (
    <div
      className={
        compact
          ? 'anlux-impersonation-engine flex min-w-0 flex-row flex-wrap items-center gap-2 sm:flex-nowrap'
          : 'anlux-impersonation-engine contents'
      }
    >
      {isImpersonating ? (
        <ImpersonationBanner nombreTecnico={nombreTecnico} onExit={() => void exitImpersonation()} />
      ) : null}
      {!isImpersonating && canSwitchAccount ? (
        <AccountSwitch
          accounts={accounts}
          userId={userId}
          value={selectValue}
          disabled={selectDisabled}
          compact={compact}
          onChange={(targetId, account) => void onAccountSelectChange(targetId, account)}
        />
      ) : null}
      {!isImpersonating ? <TechnicianName nombre={nombreTecnico} /> : null}
      <LoadingOverlay open={loading} message={loadingMessage} />
    </div>
  );
}
