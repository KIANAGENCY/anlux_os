import { anluxUrl, csrfToken } from '../http';
import {
  isAuthFailureResponse,
  isLoggingOut,
  redirectToLoginSilently,
} from '../sessionGuard';
import type {
  ApiSuccessResponse,
  CuentasResponse,
  EstadoResponse,
  PendientesResponse,
  SolicitarResponse,
} from './types';

function jsonHeaders(): Record<string, string> {
  return {
    Accept: 'application/json',
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    'X-CSRF-TOKEN': csrfToken(),
  };
}

async function fetchJson<T extends { message?: string; success?: boolean }>(
  url: string,
  init?: RequestInit,
): Promise<{ res: Response; data: T }> {
  if (typeof window !== 'undefined' && window.__anluxApplyingImpersonation) {
    /* evitar marcar logout por 401 transitorio durante aplicar */
  } else if (isLoggingOut()) {
    return {
      res: new Response(null, { status: 401 }),
      data: { success: false } as T,
    };
  }

  const res = await fetch(url, {
    credentials: 'same-origin',
    headers: jsonHeaders(),
    ...init,
  });
  const data = (await res.json().catch(() => ({}))) as T;

  if (isAuthFailureResponse(res, data) && !(typeof window !== 'undefined' && window.__anluxApplyingImpersonation)) {
    redirectToLoginSilently();
    return {
      res,
      data: { ...data, success: false, message: undefined },
    };
  }

  return { res, data };
}

export async function fetchCuentas(): Promise<{ res: Response; data: CuentasResponse }> {
  return fetchJson<CuentasResponse>(anluxUrl('/api/impersonacion/cuentas'));
}

export async function solicitarImpersonacion(targetId: number): Promise<{ res: Response; data: SolicitarResponse }> {
  return fetchJson<SolicitarResponse>(anluxUrl('/api/impersonacion/solicitar'), {
    method: 'POST',
    body: JSON.stringify({ target_id: targetId }),
  });
}

export async function fetchEstado(token: string): Promise<{ res: Response; data: EstadoResponse }> {
  return fetchJson<EstadoResponse>(anluxUrl(`/api/impersonacion/estado/${encodeURIComponent(token)}`));
}

export async function aplicarImpersonacion(token: string): Promise<{ res: Response; data: ApiSuccessResponse }> {
  return fetchJson<ApiSuccessResponse>(anluxUrl('/api/impersonacion/aplicar'), {
    method: 'POST',
    body: JSON.stringify({ token }),
  });
}

export async function cancelarImpersonacion(token: string): Promise<{ res: Response; data: ApiSuccessResponse }> {
  return fetchJson<ApiSuccessResponse>(anluxUrl('/api/impersonacion/cancelar'), {
    method: 'POST',
    body: JSON.stringify({ token }),
  });
}

export async function fetchPendientes(): Promise<{ res: Response; data: PendientesResponse }> {
  return fetchJson<PendientesResponse>(anluxUrl('/api/impersonacion/pendientes'));
}

export async function responderImpersonacion(
  requestId: number,
  approve: boolean,
): Promise<{ res: Response; data: ApiSuccessResponse }> {
  return fetchJson<ApiSuccessResponse>(anluxUrl('/api/impersonacion/responder'), {
    method: 'POST',
    body: JSON.stringify({ request_id: requestId, approve }),
  });
}

export async function salirImpersonacion(): Promise<{ res: Response; data: ApiSuccessResponse }> {
  return fetchJson<ApiSuccessResponse>(anluxUrl('/api/impersonacion/salir'), {
    method: 'POST',
  });
}
