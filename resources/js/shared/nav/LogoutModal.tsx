import { useEffect, useState } from 'react';
import { createPortal } from 'react-dom';
import { anluxUrl, csrfToken } from '../http';
import { markLoggingOut } from '../sessionGuard';

type LogoutModalProps = {
  open: boolean;
  logoutUrl: string;
  csrf: string;
  onClose: () => void;
};

function resolveCsrf(fallback: string): string {
  return csrfToken() || fallback || '';
}

function goLoginReplace(): void {
  const url = anluxUrl('/login?logged_out=1');
  try {
    window.location.replace(url);
  } catch {
    window.location.href = url;
  }
}

export function LogoutModal({ open, logoutUrl, csrf, onClose }: LogoutModalProps) {
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!open) {
      setBusy(false);
      setError(null);
      return undefined;
    }
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape' && !busy) onClose();
    };
    document.addEventListener('keydown', onKey);
    return () => document.removeEventListener('keydown', onKey);
  }, [open, onClose, busy]);

  if (!open) return null;

  const onConfirmLogout = async () => {
    if (busy) return;
    setBusy(true);
    setError(null);
    // Pause background requests without declaring the session closed yet.
    window.__anluxLoggingOut = true;

    try {
      if (typeof window.anluxPermitirSalidaOrdenForm === 'function') {
        window.anluxPermitirSalidaOrdenForm();
      }
    } catch {
      /* ignore */
    }

    const token = resolveCsrf(csrf);

    // 1) Intentar logout por fetch (cookies Set-Cookie) + replace (saca esta página del historial).
    try {
      const response = await fetch(logoutUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': token,
        },
        body: JSON.stringify({}),
        keepalive: true,
        signal: AbortSignal.timeout(15000),
      });
      const result = await response.json().catch(() => null);
      if (!response.ok || result?.success !== true) {
        throw new Error(response.status === 419
          ? 'La página ha caducado. Recárgala y vuelve a cerrar sesión.'
          : 'No se pudo cerrar la sesión. Intenta de nuevo.');
      }
      markLoggingOut();
      goLoginReplace();
    } catch (err) {
      window.__anluxLoggingOut = false;
      setBusy(false);
      setError(err instanceof Error && err.name === 'Error'
        ? err.message
        : 'No se pudo confirmar el cierre de sesión. Revisa tu conexión e intenta de nuevo.');
    }
  };

  return createPortal(
    <div
      id="modalCerrarSesion"
      className="fixed inset-0 z-[10000] flex items-center justify-center bg-blue-950/40 p-4 backdrop-blur-[2px]"
      role="dialog"
      aria-modal="true"
      aria-labelledby="modalCerrarSesionTitulo"
      onClick={(e) => {
        if (!busy && e.target === e.currentTarget) onClose();
      }}
    >
      <div className="nav-modal-panel w-full max-w-md overflow-hidden rounded-xl border-2 border-blue-200 bg-white shadow-2xl shadow-blue-900/15">
        <div className="border-b border-slate-200 bg-white px-6 py-4 text-slate-900">
          <h3 id="modalCerrarSesionTitulo" className="text-lg font-bold tracking-tight">
            <i className="fas fa-sign-out-alt mr-2 opacity-90" aria-hidden="true" />
            ¿Cerrar sesión?
          </h3>
        </div>
        <div className="border-t border-blue-100 bg-white px-6 py-5">
          {error ? <p role="alert" className="mb-4 text-sm font-semibold text-red-700">{error}</p> : null}
          <p className="mb-6 text-sm leading-relaxed text-blue-900/85">
            Vas a salir de tu cuenta en este navegador. Podrás volver a entrar cuando quieras.
          </p>
          <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button
              type="button"
              id="modalCerrarSesionCancelar"
              className="rounded-lg border-2 border-blue-300 bg-white px-5 py-2.5 text-sm font-semibold text-blue-800 shadow-sm transition hover:bg-blue-50 disabled:opacity-50"
              onClick={onClose}
              disabled={busy}
            >
              Cancelar
            </button>
            <button
              type="button"
              className="inline-flex w-full items-center justify-center rounded-lg border-2 border-red-500 bg-white px-5 py-2.5 text-sm font-bold text-red-700 shadow-sm transition hover:bg-red-50 disabled:opacity-50 sm:w-auto"
              onClick={() => void onConfirmLogout()}
              disabled={busy}
            >
              {busy ? 'Saliendo…' : 'Cerrar sesión'}
            </button>
          </div>
        </div>
      </div>
    </div>,
    document.body,
  );
}
