import type { EstatusFiltro, OrdenesListResponse, SortOrdenes, UpdateStatusResponse } from './types';

export function anluxBaseUrl(): string {
  return String(window.ANLUX_BASE_URL || '').replace(/\/$/, '');
}

export function anluxUrl(path: string): string {
  const base = anluxBaseUrl();
  return `${base}${path.startsWith('/') ? path : `/${path}`}`;
}

export function csrfToken(): string {
  return String(window.ANLUX_CSRF_TOKEN || '');
}

async function parseJsonOrRedirect(response: Response): Promise<unknown> {
  const contentType = String(response.headers.get('content-type') || '').toLowerCase();
  if (!response.ok || !contentType.includes('application/json')) {
    if (response.status === 401 || response.status === 419 || response.redirected) {
      window.location.href = anluxUrl('/login');
      throw new Error('Sesión expirada');
    }
    throw new Error('Respuesta no válida del servidor');
  }
  return response.json();
}

export async function fetchOrdenes(params: {
  search: string;
  startDate: string;
  endDate: string;
  estatus: EstatusFiltro;
  page: number;
  perPage: number;
  sort: SortOrdenes;
}): Promise<OrdenesListResponse> {
  const qs = new URLSearchParams();
  qs.set('search', params.search);
  qs.set('startDate', params.startDate);
  qs.set('endDate', params.endDate);
  qs.set('estatus', params.estatus);
  qs.set('page', String(params.page));
  qs.set('perPage', String(params.perPage));
  qs.set('sort', params.sort);

  const response = await fetch(anluxUrl(`/api/ordenes?${qs.toString()}`), {
    credentials: 'same-origin',
    headers: {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    },
  });

  return (await parseJsonOrRedirect(response)) as OrdenesListResponse;
}

export async function updateOrdenEstatus(id: string | number, estatus: string): Promise<UpdateStatusResponse> {
  const formData = new FormData();
  formData.append('id', String(id));
  formData.append('estatus', estatus);
  const token = csrfToken();
  if (token) {
    formData.append('_token', token);
  }

  const response = await fetch(anluxUrl('/api/ordenes/estatus'), {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: formData,
  });

  return (await parseJsonOrRedirect(response)) as UpdateStatusResponse;
}

export function urlPdfOrden(id: string | number, inline: boolean): string {
  const bust = `${Date.now()}_${Math.random().toString(36).slice(2, 8)}`;
  const q = inline ? 'inline=1&refresh_pdf=1' : 'refresh_pdf=1';
  return anluxUrl(`/pdf/orden/${encodeURIComponent(String(id))}?${q}&nocache=1&_=${bust}`);
}

export async function abrirPdfOrdenSinCache(id: string | number, inline = true): Promise<void> {
  const url = urlPdfOrden(id, inline);
  try {
    const res = await fetch(url, {
      method: 'GET',
      credentials: 'same-origin',
      cache: 'no-store',
      headers: {
        Accept: 'application/pdf',
        'Cache-Control': 'no-cache',
        Pragma: 'no-cache',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });
    if (!res.ok) {
      throw new Error(`HTTP ${res.status}`);
    }
    const raw = await res.blob();
    const pdfBlob = raw.type && raw.type.includes('pdf')
      ? raw
      : new Blob([raw], { type: 'application/pdf' });
    const objUrl = URL.createObjectURL(pdfBlob);
    const ventana = window.open(objUrl, '_blank');
    if (ventana) {
      try {
        ventana.opener = null;
      } catch {
        /* ignore */
      }
    } else {
      window.open(url, '_blank');
    }
    setTimeout(() => {
      try {
        URL.revokeObjectURL(objUrl);
      } catch {
        /* ignore */
      }
    }, 180000);
  } catch (err) {
    console.warn('PDF sin cache falló; abriendo URL directa', err);
    window.open(url, '_blank');
  }
}
