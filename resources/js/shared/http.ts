export function anluxBaseUrl(): string {
  return String(window.ANLUX_BASE_URL || '').replace(/\/$/, '');
}

export function anluxUrl(path: string): string {
  const base = anluxBaseUrl();
  return `${base}${path.startsWith('/') ? path : `/${path}`}`;
}

export function csrfToken(): string {
  return (
    String(window.ANLUX_CSRF_TOKEN || '')
    || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    || ''
  );
}

export async function parseJsonOrRedirect(response: Response): Promise<unknown> {
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

export async function abrirPdfSinCache(pathOrUrl: string): Promise<void> {
  const url = pathOrUrl.startsWith('http') ? pathOrUrl : anluxUrl(pathOrUrl);
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
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
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
    console.warn('PDF sin cache falló', err);
    window.open(url, '_blank');
  }
}

export function readPageProps<T>(elementId = 'react-page-props'): T | null {
  const el = document.getElementById(elementId);
  if (!el?.textContent) return null;
  try {
    return JSON.parse(el.textContent) as T;
  } catch {
    return null;
  }
}
