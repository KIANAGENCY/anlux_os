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
  const isJson = contentType.includes('application/json');

  if (isJson) {
    const data = await response.json();
    if (response.status === 401 || response.status === 419 || response.redirected) {
      const { redirectToLoginSilently } = await import('./sessionGuard');
      redirectToLoginSilently();
      throw new Error('Sesión expirada');
    }
    if (!response.ok) {
      return data;
    }
    return data;
  }

  if (!response.ok) {
    if (response.status === 401 || response.status === 419 || response.redirected) {
      const { redirectToLoginSilently } = await import('./sessionGuard');
      redirectToLoginSilently();
      throw new Error('Sesión expirada');
    }
    throw new Error('Respuesta no válida del servidor');
  }

  return response.json();
}

export async function abrirPdfSinCache(pathOrUrl: string): Promise<void> {
  const url = pathOrUrl.startsWith('http') ? pathOrUrl : anluxUrl(pathOrUrl);
  // Abrir al instante: el navegador descarga/renderiza mientras el servidor responde.
  const ventana = window.open(url, '_blank', 'noopener,noreferrer');
  if (ventana) {
    try {
      ventana.opener = null;
    } catch {
      /* ignore */
    }
    return;
  }
  try {
    const a = document.createElement('a');
    a.href = url;
    a.target = '_blank';
    a.rel = 'noopener noreferrer';
    document.body.appendChild(a);
    a.click();
    a.remove();
  } catch {
    window.location.assign(url);
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
