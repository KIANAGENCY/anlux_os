import { csrfToken, parseJsonOrRedirect, abrirPdfSinCache } from '../shared/http';
import type { ApiOkResponse, RegistrarResponse, WhatsappEstadoResponse } from './types';

function withCsrf(fd: FormData, token?: string): FormData {
  const t = token || csrfToken();
  if (t && !fd.has('_token')) {
    fd.append('_token', t);
  }
  return fd;
}

export async function registrarOrden(url: string, formData: FormData, csrf?: string): Promise<RegistrarResponse> {
  withCsrf(formData, csrf);
  const response = await fetch(url, {
    method: 'POST',
    body: formData,
    credentials: 'same-origin',
    headers: {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    },
  });
  return (await parseJsonOrRedirect(response)) as RegistrarResponse;
}

export async function lockHeartbeat(url: string, csrf?: string): Promise<ApiOkResponse> {
  if (!url) return { success: false };
  const fd = withCsrf(new FormData(), csrf);
  const response = await fetch(url, {
    method: 'POST',
    body: fd,
    credentials: 'same-origin',
    headers: {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    },
  });
  return (await parseJsonOrRedirect(response)) as ApiOkResponse;
}

export async function lockRelease(url: string, csrf?: string): Promise<void> {
  if (!url) return;
  const fd = withCsrf(new FormData(), csrf);
  try {
    if (typeof navigator !== 'undefined' && typeof navigator.sendBeacon === 'function') {
      navigator.sendBeacon(url, fd);
      return;
    }
  } catch {
    /* fallback fetch */
  }
  try {
    await fetch(url, {
      method: 'POST',
      body: fd,
      credentials: 'same-origin',
      keepalive: true,
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });
  } catch {
    /* ignore unload errors */
  }
}

export async function salidaTemporal(
  url: string,
  payload: { id_equipo: number; motivo: string; firma_cliente?: string; firma_tecnico?: string },
  csrf?: string,
): Promise<ApiOkResponse> {
  const response = await fetch(url, {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': csrf || csrfToken(),
    },
    body: JSON.stringify({
      id_equipo: payload.id_equipo,
      motivo: payload.motivo,
      firma_cliente: payload.firma_cliente || '',
      firma_tecnico: payload.firma_tecnico || '',
      _token: csrf || csrfToken(),
    }),
  });
  return (await parseJsonOrRedirect(response)) as ApiOkResponse;
}

export async function regresoTemporal(url: string, csrf?: string): Promise<ApiOkResponse> {
  const fd = withCsrf(new FormData(), csrf);
  const response = await fetch(url, {
    method: 'POST',
    body: fd,
    credentials: 'same-origin',
    headers: {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    },
  });
  return (await parseJsonOrRedirect(response)) as ApiOkResponse;
}

export async function reenviarRecepcion(
  url: string,
  csrf?: string,
  cliente?: {
    nombreCliente?: string;
    telefono?: string;
    correo?: string;
    direccion?: string;
    poblacion?: string;
  },
): Promise<ApiOkResponse> {
  const fd = withCsrf(new FormData(), csrf);
  if (cliente) {
    fd.append('nombreCliente', String(cliente.nombreCliente || ''));
    fd.append('telefono', String(cliente.telefono || ''));
    fd.append('correo', String(cliente.correo || '').trim().toLowerCase());
    fd.append('direccion', String(cliente.direccion || ''));
    fd.append('poblacion', String(cliente.poblacion || ''));
  }
  const response = await fetch(url, {
    method: 'POST',
    body: fd,
    credentials: 'same-origin',
    headers: {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    },
  });
  return (await parseJsonOrRedirect(response)) as ApiOkResponse;
}

export async function pollWhatsappEstado(
  baseUrl: string,
  notificationId: number,
  csrf?: string,
): Promise<WhatsappEstadoResponse> {
  const root = baseUrl.replace(/\/$/, '');
  const url = `${root}/api/ordenes/whatsapp-estado/${notificationId}`;
  const response = await fetch(url, {
    method: 'GET',
    credentials: 'same-origin',
    headers: {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': csrf || csrfToken(),
    },
  });
  return (await parseJsonOrRedirect(response)) as WhatsappEstadoResponse;
}

export async function abrirPdfOrden(pdfUrl: string): Promise<void> {
  if (!pdfUrl) return;
  await abrirPdfSinCache(pdfUrl);
}
