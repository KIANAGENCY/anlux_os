import { useCallback, useEffect, useState } from 'react';
import { ImpersonationEngine } from './ImpersonationEngine';
import { DialogProvider } from './ui';
import type { NavAppBootstrap } from './types';

function LogoutModal({
  open,
  logoutUrl,
  csrf,
  onClose,
}: {
  open: boolean;
  logoutUrl: string;
  csrf: string;
  onClose: () => void;
}) {
  useEffect(() => {
    if (!open) return undefined;
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') onClose();
    };
    document.addEventListener('keydown', onKey);
    return () => document.removeEventListener('keydown', onKey);
  }, [open, onClose]);

  if (!open) return null;

  const permitirSalida = () => {
    try {
      if (typeof window.anluxPermitirSalidaOrdenForm === 'function') {
        window.anluxPermitirSalidaOrdenForm();
      }
    } catch {
      /* ignore */
    }
  };

  return (
    <div
      id="modalCerrarSesion"
      className="fixed inset-0 z-[100] flex items-center justify-center bg-blue-950/40 p-4 backdrop-blur-[2px]"
      role="dialog"
      aria-modal="true"
      aria-labelledby="modalCerrarSesionTitulo"
      onClick={(e) => {
        if (e.target === e.currentTarget) onClose();
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
          <p className="mb-6 text-sm leading-relaxed text-blue-900/85">
            Vas a salir de tu cuenta en este navegador. Podrás volver a entrar cuando quieras.
          </p>
          <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button
              type="button"
              id="modalCerrarSesionCancelar"
              className="rounded-lg border-2 border-blue-300 bg-white px-5 py-2.5 text-sm font-semibold text-blue-800 shadow-sm transition hover:bg-blue-50"
              onClick={onClose}
            >
              Cancelar
            </button>
            <form method="POST" action={logoutUrl} className="inline" onSubmit={permitirSalida}>
              <input type="hidden" name="_token" value={csrf} />
              <button
                type="submit"
                className="inline-flex w-full items-center justify-center rounded-lg border-2 border-red-500 bg-white px-5 py-2.5 text-sm font-bold text-red-700 shadow-sm transition hover:bg-red-50 sm:w-auto"
              >
                Cerrar sesión
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  );
}

export function NavAppBar(props: NavAppBootstrap) {
  const [logoutOpen, setLogoutOpen] = useState(false);
  const openLogout = useCallback(() => {
    setLogoutOpen(true);
    document.body.style.overflow = 'hidden';
  }, []);
  const closeLogout = useCallback(() => {
    setLogoutOpen(false);
    document.body.style.overflow = '';
  }, []);

  return (
    <DialogProvider>
      <nav
        className="sticky top-0 z-50 mb-6 flex flex-col gap-3 rounded-xl border border-slate-200 bg-white/95 p-3 shadow-sm backdrop-blur-sm lg:flex-row lg:items-end lg:justify-between"
        aria-label="Navegación principal"
        style={{
          position: 'sticky',
          top: 0,
          zIndex: 9999,
          visibility: 'visible',
          opacity: 1,
          backdropFilter: 'blur(2px)',
        }}
      >
        <div className="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
          <a
            href={props.ordenesUrl}
            className="anlux-logo-card shrink-0"
            aria-label="Ir a órdenes registradas"
          >
            <img
              src={props.logoUrl}
              alt="Logo Anlux"
              className="h-12 w-auto object-contain sm:h-14"
              style={{ maxWidth: 230 }}
            />
          </a>
        </div>
        <div className="flex min-w-0 flex-row flex-wrap items-end justify-end gap-3 self-stretch border-t border-slate-200 pt-3 lg:border-l lg:border-t-0 lg:pl-4 lg:pt-0">
          <ImpersonationEngine
            isImpersonating={props.isImpersonating}
            canSwitchAccount={props.canSwitchAccount}
            userId={props.userId}
            initialAccounts={props.accounts}
            nombreTecnico={props.nombreTecnico}
          />
          <button
            type="button"
            id="navBtnCerrarSesion"
            className="anlux-btn-danger"
            onClick={openLogout}
          >
            <i className="fas fa-sign-out-alt" aria-hidden="true" />
            Cerrar sesión
          </button>
        </div>
      </nav>
      <LogoutModal open={logoutOpen} logoutUrl={props.logoutUrl} csrf={props.csrf} onClose={closeLogout} />
    </DialogProvider>
  );
}
