import { ImpersonationEngine } from './ImpersonationEngine';
import { DialogProvider } from './ui';
import type { AdminNavLink, NavAdminBootstrap } from './types';

function AdminLink({ link, active }: { link: AdminNavLink; active: boolean }) {
  const classes = active
    ? 'inline-flex min-h-10 shrink-0 items-center gap-2 rounded-lg bg-blue-700 px-3 text-sm font-semibold text-white shadow-sm transition'
    : 'inline-flex min-h-10 shrink-0 items-center gap-2 rounded-lg px-3 text-sm font-semibold text-slate-700 transition hover:bg-blue-50 hover:text-blue-800';

  return (
    <a href={link.href} className={classes}>
      <i className={`fas ${link.icon}`} aria-hidden="true" />
      <span>{link.label}</span>
    </a>
  );
}

/**
 * Logo card (como Exacto) + nav admin en una fila en lg.
 */
export function NavAdminBar(props: NavAdminBootstrap) {
  return (
    <DialogProvider>
      <div className="mb-4">
        {props.logoUrl ? (
          <div className="anlux-logo-card mb-3">
            <img
              src={props.logoUrl}
              alt="Anlux"
              className="h-12 w-auto object-contain sm:h-14"
              style={{ maxWidth: 200 }}
            />
          </div>
        ) : null}

        <nav
          className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
          aria-label="Navegación admin"
        >
          <div>
            <div className="flex min-h-14 flex-nowrap items-center gap-1.5 overflow-x-auto border-b border-slate-200 px-3 py-2 sm:gap-2">
              {props.adminLinks.map((link) => (
                <AdminLink key={link.key} link={link} active={props.navAdminActivo === link.key} />
              ))}
            </div>

            <div className="flex flex-col gap-3 px-3 py-3 md:flex-row md:flex-wrap md:items-end md:justify-end">
              <ImpersonationEngine
                isImpersonating={props.isImpersonating}
                canSwitchAccount={props.canSwitchAccount}
                userId={props.userId}
                initialAccounts={props.accounts}
                nombreTecnico={props.nombreTecnico}
                compact={false}
              />
              <a
                href={props.ordenesUrl}
                className="anlux-btn-secondary shrink-0 border-blue-200 text-blue-800"
              >
                <i className="fas fa-arrow-left" aria-hidden="true" />
                Órdenes
              </a>
              <form
                method="POST"
                action={props.logoutUrl}
                className="inline shrink-0"
                onSubmit={() => {
                  try {
                    if (typeof window.anluxPermitirSalidaOrdenForm === 'function') {
                      window.anluxPermitirSalidaOrdenForm();
                    }
                  } catch {
                    /* ignore */
                  }
                }}
              >
                <input type="hidden" name="_token" value={props.csrf} />
                <button
                  type="submit"
                  className="anlux-btn-danger"
                >
                  <i className="fas fa-sign-out-alt" aria-hidden="true" />
                  Cerrar sesión
                </button>
              </form>
            </div>
          </div>
        </nav>
      </div>
    </DialogProvider>
  );
}
