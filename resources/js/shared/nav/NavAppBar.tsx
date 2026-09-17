import { useCallback, useState } from 'react';
import { IconSignOut } from '../icons';
import { ImpersonationEngine } from './ImpersonationEngine';
import { LogoutModal } from './LogoutModal';
import { DialogProvider } from './ui';
import type { NavAppBootstrap } from './types';

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
        className="sticky top-0 z-50 mb-4 flex flex-col gap-3 rounded-xl border border-[color:var(--anlux-border-soft)] bg-[color:var(--anlux-surface)]/95 p-3 shadow-sm backdrop-blur-sm lg:flex-row lg:items-end lg:justify-between"
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
        <div className="flex min-w-0 flex-row flex-wrap items-end justify-end gap-3 self-stretch border-t border-[color:var(--anlux-border-soft)] pt-3 lg:border-l lg:border-t-0 lg:pl-4 lg:pt-0">
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
            <span className="anlux-icon-box">
              <IconSignOut size={20} />
            </span>
            Cerrar sesión
          </button>
        </div>
      </nav>
      <LogoutModal open={logoutOpen} logoutUrl={props.logoutUrl} csrf={props.csrf} onClose={closeLogout} />
    </DialogProvider>
  );
}
