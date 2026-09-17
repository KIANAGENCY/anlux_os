import { anluxUrl } from './http';

declare global {
  interface Window {
    __anluxLoggingOut?: boolean;
    __anluxRedirectingToLogin?: boolean;
    __anluxAuthDead?: boolean;
    __anluxApplyingImpersonation?: boolean;
  }
}

const FLAG_KEY = 'anlux_logged_out';
const CLIENT_COOKIE = 'anlux_client_logged_out';

function setClientLogoutCookie(): void {
  try {
    const secure = window.location.protocol === 'https:' ? '; Secure' : '';
    document.cookie = `${CLIENT_COOKIE}=1; Path=/; Max-Age=3600; SameSite=Lax${secure}`;
  } catch {
    /* ignore */
  }
}

function clearClientLogoutCookie(): void {
  try {
    const secure = window.location.protocol === 'https:' ? '; Secure' : '';
    document.cookie = `${CLIENT_COOKIE}=; Path=/; Max-Age=0; SameSite=Lax${secure}`;
  } catch {
    /* ignore */
  }
}

export function hasLoggedOutFlag(): boolean {
  // Shared state wins: another tab may already have signed in again.
  try {
    if (localStorage.getItem(FLAG_KEY)) return true;
  } catch {
    /* ignore */
  }
  try {
    if (document.cookie.split(';').some((c) => c.trim().startsWith(`${CLIENT_COOKIE}=1`))) {
      return true;
    }
  } catch {
    /* ignore */
  }
  return false;
}

export function isLoggingOut(): boolean {
  return Boolean(
    window.__anluxLoggingOut
    || window.__anluxRedirectingToLogin
    || window.__anluxAuthDead
    || hasLoggedOutFlag(),
  );
}

export function markLoggingOut(): void {
  window.__anluxLoggingOut = true;
  window.__anluxAuthDead = true;
  const stamp = String(Date.now());
  try {
    sessionStorage.setItem(FLAG_KEY, stamp);
  } catch {
    /* ignore */
  }
  try {
    localStorage.setItem(FLAG_KEY, stamp);
  } catch {
    /* ignore */
  }
  setClientLogoutCookie();
}

export function clearLoggedOutFlags(): void {
  window.__anluxLoggingOut = false;
  window.__anluxAuthDead = false;
  window.__anluxRedirectingToLogin = false;
  try {
    sessionStorage.removeItem(FLAG_KEY);
  } catch {
    /* ignore */
  }
  try {
    localStorage.removeItem(FLAG_KEY);
  } catch {
    /* ignore */
  }
  clearClientLogoutCookie();
}

/** True for Laravel auth failures and similar session-end payloads. */
export function isAuthFailureMessage(message: unknown): boolean {
  if (message == null) return false;
  const text = Array.isArray(message)
    ? message.map(String).join(' ')
    : typeof message === 'object'
      ? JSON.stringify(message)
      : String(message);
  const normalized = text.trim().toLowerCase();
  if (!normalized) return false;
  return (
    normalized.includes('unauthenticated')
    || normalized.includes('unauthaticated')
    || normalized.includes('csrf token mismatch')
    || normalized.includes('page expired')
    || normalized.includes('sesión cerrada')
    || normalized.includes('sesion cerrada')
    || normalized.includes('inicia sesión')
    || normalized.includes('iniciar sesión')
    || normalized === 'unauthorized'
  );
}

export function isAuthFailureResponse(res: Response, data?: { message?: unknown; success?: boolean } | null): boolean {
  if (isLoggingOut()) return true;
  if (res.status === 401 || res.status === 419) return true;
  return isAuthFailureMessage(data?.message);
}

/** Redirect once to login; never show auth-error modals. */
export function redirectToLoginSilently(): void {
  // The logout request owns navigation until the server confirms it.
  if (window.__anluxLoggingOut && !window.__anluxAuthDead) return;
  markLoggingOut();
  if (window.__anluxRedirectingToLogin) return;
  window.__anluxRedirectingToLogin = true;
  try {
    window.location.replace(anluxUrl('/login?logged_out=1'));
  } catch {
    window.location.href = anluxUrl('/login?logged_out=1');
  }
}
