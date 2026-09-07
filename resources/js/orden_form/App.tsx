import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
  abrirPdfOrden,
  lockHeartbeat,
  lockRelease,
  pollWhatsappEstado,
  registrarOrden,
  reenviarRecepcion,
  regresoTemporal,
  salidaTemporal,
} from './api';
import AnticiposSection, { emptyAnticipo } from './components/AnticiposSection';
import ClienteSection from './components/ClienteSection';
import ComentariosTecnicoSection from './components/ComentariosTecnicoSection';
import EstatusSelect from './components/EstatusSelect';
import { EntregaConfirmModal, EntregaFirmasModal } from './components/EntregaEquipoModals';
import EquiposSection, { emptyEquipo } from './components/EquiposSection';
import FirmasSection from './components/FirmasSection';
import LiquidarSaldoModal from './components/LiquidarSaldoModal';
import MaterialesSection, { emptyMaterial } from './components/MaterialesSection';
import ObservacionesSection from './components/ObservacionesSection';
import SalidaTemporalModal from './components/SalidaTemporalModal';
import TotalesBar, { calcTotalesFromForm } from './components/TotalesBar';
import TrabajosSection, { emptyTrabajo } from './components/TrabajosSection';
import { DialogProvider, useAnluxDialog, type DialogContextValue } from '../shared/nav/ui';
import { abonoParaLiquidarSaldo, anluxMontoConIva } from './lib/iva';
import {
  borrarBorrador,
  borradorStorageKey,
  borradorTieneContenidoUtil,
  formatFechaBorrador,
  formatHoraBorrador,
  guardarBorrador,
  leerBorrador,
  recolectarBorrador,
} from './lib/ordenDraft';
import { focusAnluxField } from './lib/focusField';
import { flushAllNetoInputs } from './lib/netoFlush';
import {
  validarAnticipos,
  validarEntregadoLiquidado,
  validarEquipos,
  validarMateriales,
  validarObservaciones,
  validarPoblacion,
  validarServiciosExtra,
} from './lib/validaciones';
import type {
  AnticipoForm,
  ClienteState,
  EquipoForm,
  MaterialForm,
  OrdenFormBootstrap,
  SalidaTemporalPayload,
  ServicioSersop,
  SignaturePadHandle,
  TrabajoForm,
} from './types';
import { FIRMA_PNG_VACIA } from './types';

/** Paridad con anluxHayNumerosNegativos (legacy). */
function hayNumerosNegativos(
  trabajos: TrabajoForm[],
  materiales: MaterialForm[],
  anticipos: AnticipoForm[],
): boolean {
  for (const t of trabajos) {
    if ((Number(t.importe) || 0) < 0) return true;
  }
  for (const m of materiales) {
    if ((Number(m.cant) || 0) < 0 || (Number(m.precio) || 0) < 0) return true;
  }
  for (const a of anticipos) {
    if ((Number(a.monto) || 0) < 0) return true;
  }
  return false;
}

/**
 * Paridad con anluxConfirmarSaldoLiquidadoAlGuardar (modales Anlux, no navegador).
 */
async function confirmarSaldoAlGuardar(
  dialogs: Pick<DialogContextValue, 'showAlert' | 'showConfirm'>,
  input: {
    total: number;
    saldoPendiente: number;
    anticiposSinIva: number;
    abonoSaldo: number;
    yaConfirmado?: boolean;
  },
): Promise<{ ok: boolean; abonoFinal: number; saldoPagadoConfirmado: boolean }> {
  const { total, saldoPendiente, anticiposSinIva, abonoSaldo, yaConfirmado = false } = input;
  if (total <= 0.009) {
    return { ok: true, abonoFinal: abonoSaldo, saldoPagadoConfirmado: false };
  }

  const huboPago = anticiposSinIva + abonoSaldo > 0.009;

  if (saldoPendiente > 0.009) {
    const clientePago = await dialogs.showConfirm(
      `Hay saldo pendiente de $${saldoPendiente.toFixed(2)}. ¿El cliente ya pagó?\n\n`
        + '• Aceptar: liquidar saldo y guardar.\n'
        + '• Cancelar: guardar con saldo pendiente.',
      {
        title: 'Confirmar pago',
        icon: 'warning',
        confirmText: 'Sí, liquidar y guardar',
        cancelText: 'No, guardar con saldo',
      },
    );
    if (clientePago) {
      return {
        ok: true,
        abonoFinal: abonoParaLiquidarSaldo(total, anticiposSinIva),
        saldoPagadoConfirmado: true,
      };
    }
    return { ok: true, abonoFinal: abonoSaldo, saldoPagadoConfirmado: false };
  }

  if (Math.abs(saldoPendiente) <= 0.009 && huboPago) {
    if (yaConfirmado) {
      return { ok: true, abonoFinal: abonoSaldo, saldoPagadoConfirmado: true };
    }
    const clientePago = await dialogs.showConfirm(
      'El saldo pendiente quedó en $0.00. ¿Confirmas que el cliente pagó?',
      {
        title: 'Confirmar pago',
        icon: 'warning',
        confirmText: 'Sí, cliente pagó',
        cancelText: 'Cancelar',
      },
    );
    if (!clientePago) {
      await dialogs.showAlert('Guardado cancelado. Confirma el pago del cliente para continuar.', {
        title: 'Operación cancelada',
        icon: 'warning',
      });
      return { ok: false, abonoFinal: abonoSaldo, saldoPagadoConfirmado: false };
    }
    return { ok: true, abonoFinal: abonoSaldo, saldoPagadoConfirmado: true };
  }

  return { ok: true, abonoFinal: abonoSaldo, saldoPagadoConfirmado: false };
}

function toDatetimeLocalValue(raw: unknown): string {
  const s = String(raw || '').trim();
  if (!s) {
    const d = new Date();
    const pad = (n: number) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
  }
  const normalized = s.includes('T') ? s : s.replace(' ', 'T');
  return normalized.slice(0, 16);
}

function str(v: unknown): string {
  return v == null ? '' : String(v);
}

function buildInitialState(bootstrap: OrdenFormBootstrap): {
  cliente: ClienteState;
  equipos: EquipoForm[];
  observaciones: string[];
  trabajos: TrabajoForm[];
  materiales: MaterialForm[];
  anticipos: AnticipoForm[];
  abono_saldo: number;
  comentarios_tecnico: string;
} {
  const orden = bootstrap.orden;
  const cab = (orden?.cab || {}) as Record<string, unknown>;
  const t = (orden?.t || {}) as Record<string, unknown>;
  const folio = orden ? str(cab.folio || bootstrap.meta.folio_preview) : bootstrap.meta.folio_preview;
  const estatus = orden ? str(cab.estatus || 'Recepcion') : 'Recepcion';

  const equiposRaw = orden?.equipos || [];
  const equipos: EquipoForm[] = equiposRaw.length
    ? equiposRaw.map((eq) => ({
        id_equipo: (eq.id_equipo as number | null | undefined) ?? null,
        marca: str(eq.marca).toUpperCase(),
        modelo: str(eq.modelo).toUpperCase(),
        serie: str(eq.serie).toUpperCase(),
        descripcionFalla: str(eq.descripcion_falla || eq.descripcion).toUpperCase(),
        tipoServicio: str(eq.tipo_servicio),
        acciones: Number(eq.acciones) || 0,
        entrega_receptor_tipo: eq.entrega_receptor_tipo == null ? null : str(eq.entrega_receptor_tipo),
        entrega_recibido_cliente: eq.entrega_recibido_cliente == null ? null : str(eq.entrega_recibido_cliente),
        entrega_fecha: eq.entrega_fecha == null ? null : str(eq.entrega_fecha),
        entrega_tecnico: eq.entrega_tecnico == null ? null : str(eq.entrega_tecnico),
      }))
    : [emptyEquipo()];

  const trabajosRaw = orden?.trabajos || [];
  const trabajos: TrabajoForm[] = trabajosRaw.length
    ? trabajosRaw.map((t) => ({
        clave: str(t.clave),
        descripcion: str(t.descripcion).toUpperCase(),
        importe: t.importe == null || t.importe === '' ? '' : String(Number(t.importe)),
        ticket: str(t.ticket).toUpperCase(),
        id_equipo: t.id_equipo == null || t.id_equipo === '' ? '' : String(t.id_equipo),
      }))
    : [emptyTrabajo()];

  const materialesRaw = orden?.materiales || [];
  const materiales: MaterialForm[] = materialesRaw.length
    ? materialesRaw.map((m) => ({
        vale: str(m.vale).toUpperCase(),
        codigo: str(m.codigo).toUpperCase(),
        cant: m.cantidad != null ? String(m.cantidad) : str(m.cant),
        descripcion: str(m.descripcion).toUpperCase(),
        precio: m.precio_unitario != null ? String(m.precio_unitario) : str(m.precio),
        ticket: str(m.ticket).toUpperCase(),
        id_equipo: m.id_equipo == null || m.id_equipo === '' ? '' : String(m.id_equipo),
      }))
    : [emptyMaterial()];

  const anticiposRaw = orden?.anticipos || [];
  const anticipos: AnticipoForm[] = anticiposRaw.length
    ? anticiposRaw.map((a) => ({
        folio: str(a.folio).toUpperCase(),
        descripcion: str(a.descripcion).toUpperCase(),
        monto: a.monto == null || a.monto === '' ? '' : String(Number(a.monto)),
        ticket: str(a.ticket).toUpperCase(),
        id_equipo: a.id_equipo == null || a.id_equipo === '' ? '' : String(a.id_equipo),
      }))
    : [emptyAnticipo()];

  const obs = orden?.observaciones_items?.length ? [...orden.observaciones_items] : [''];

  return {
    cliente: {
      nombreCliente: str(cab.nombre_cliente).toUpperCase(),
      direccion: str(cab.direccion).toUpperCase(),
      telefono: str(cab.telefono).replace(/\D/g, '').slice(0, 20),
      correo: str(cab.correo),
      poblacion: str(cab.poblacion).toUpperCase(),
      folio,
      fechaEntrada: toDatetimeLocalValue(cab.fecha_entrada),
      estatus: normalizeEstatus(estatus, bootstrap.catalogs.estatus_flujo),
    },
    equipos,
    observaciones: obs,
    trabajos,
    materiales,
    anticipos,
    abono_saldo: Number(orden?.abono_saldo) || 0,
    comentarios_tecnico: str(t.comentarios_m).toUpperCase(),
  };
}

function normalizeEstatus(raw: string, flujo: string[]): string {
  const key = raw.trim().toLowerCase().replace(/\s+/g, '');
  const map: Record<string, string> = {
    recepcion: 'Recepcion',
    recepción: 'Recepcion',
    enproceso: 'En proceso',
    proceso: 'En proceso',
    terminado: 'Terminado',
    entregado: 'Entregado',
  };
  const canon = map[key] || map[raw.trim().toLowerCase()] || raw;
  return flujo.includes(canon) ? canon : (flujo[0] || 'Recepcion');
}

function getSersop01(servicios: ServicioSersop[]): ServicioSersop {
  const found = servicios.find((s) => String(s.clave || '').trim().toUpperCase() === 'SERSOP01');
  return found || {
    clave: 'SERSOP01',
    descripcion: 'REVISION / DIAGNOSTICO',
    precio: 603.45,
  };
}

type NotificationResult = { message: string; level: string; status: string; settled: boolean };

async function waitWhatsappSettled(rootUrl: string, notificationId: number, csrf: string): Promise<NotificationResult> {
  const maxTries = 12;
  let lastStatus = 'queued';
  let lastMessage = '';
  for (let i = 0; i < maxTries; i += 1) {
    try {
      const res = await pollWhatsappEstado(rootUrl, notificationId, csrf);
      lastStatus = String(res.status || lastStatus);
      lastMessage = String(res.message || lastMessage);
      if (res.settled) {
        return {
          message: String(res.message || (res.level === 'success' ? 'WhatsApp entregado al cliente.' : 'WhatsApp no entregado.')),
          level: String(res.level || 'error'),
          status: lastStatus,
          settled: true,
        };
      }
    } catch {
      /* ignore poll errors */
    }
    await new Promise((r) => window.setTimeout(r, 1500));
  }
  return {
    message: lastMessage || 'Plantilla enviada a Meta; la entrega al cliente todavía no ha sido confirmada.',
    level: 'warning',
    status: lastStatus,
    settled: false,
  };
}

function emailStatusText(notice?: string, level?: string): string {
  const message = String(notice || '').trim();
  if (!message) return 'Correo: no aplica o no se generó un aviso.';
  const normalized = String(level || '').toLowerCase();
  if (normalized === 'error') return `Correo: NO ENVIADO.\n${message}`;
  if (normalized === 'confirmed') return `Correo: CONFIRMADO Y ENVIADO.\n${message}`;
  if (normalized === 'success') return `Correo: envío aceptado.\n${message}`;
  if (normalized === 'warning') return `Correo: envío no confirmado.\n${message}`;
  return `Correo: ${message}`;
}

function whatsappStatusText(notice?: string, level?: string, status?: string): string {
  const message = String(notice || '').trim();
  if (!message) return 'WhatsApp: no aplica o no se generó un aviso.';
  const normalized = String(level || '').toLowerCase();
  if (normalized === 'error') return `WhatsApp: NO ENTREGADO.\n${message}`;
  if (status === 'delivered' || status === 'read') return `WhatsApp: ENTREGADO AL CLIENTE.\n${message}`;
  if (normalized === 'warning') return `WhatsApp: PENDIENTE DE CONFIRMACIÓN.\n${message}`;
  if (normalized === 'success') return `WhatsApp: PLANTILLA ACEPTADA POR META.\n${message}`;
  return `WhatsApp: ${message}`;
}

type Props = { bootstrap: OrdenFormBootstrap | null };

export default function App({ bootstrap }: Props) {
  if (!bootstrap) {
    return (
      <div className="rounded-lg border-2 border-red-300 bg-red-50 p-6 text-red-900">
        <p className="font-bold">
          <i className="fas fa-exclamation-triangle mr-2" />
          No se cargaron los datos del formulario (#react-page-props).
        </p>
        <p className="mt-2 text-sm">Recarga la pagina o verifica que el servidor inyecte el bootstrap JSON.</p>
      </div>
    );
  }

  return (
    <DialogProvider>
      <OrdenFormApp bootstrap={bootstrap} />
    </DialogProvider>
  );
}

function OrdenFormApp({ bootstrap }: { bootstrap: OrdenFormBootstrap }) {
  const { showAlert, showConfirm, showPrompt } = useAnluxDialog();
  const initial = useMemo(() => buildInitialState(bootstrap), [bootstrap]);
  const [cliente, setCliente] = useState(initial.cliente);
  const [equipos, setEquipos] = useState(initial.equipos);
  const [observaciones, setObservaciones] = useState(initial.observaciones);
  const [trabajos, setTrabajos] = useState(initial.trabajos);
  const [materiales, setMateriales] = useState(initial.materiales);
  const [anticipos, setAnticipos] = useState(initial.anticipos);
  const [abonoSaldo, setAbonoSaldo] = useState(initial.abono_saldo);
  const [comentariosTecnico, setComentariosTecnico] = useState(initial.comentarios_tecnico);
  const [abonoSaldoEquipos, setAbonoSaldoEquipos] = useState<Record<string, number>>({});
  const [saldoPagadoConfirmadoFlag, setSaldoPagadoConfirmadoFlag] = useState(initial.abono_saldo > 0.009);
  const [liquidarOpen, setLiquidarOpen] = useState(false);
  const [firmasOff, setFirmasOff] = useState(bootstrap.meta.firmas_deshabilitadas);
  const [firmasReactivadas, setFirmasReactivadas] = useState(false);
  const [formDirty, setFormDirty] = useState(false);
  const [draftBanner, setDraftBanner] = useState('');
  const [sersop01Hidden, setSersop01Hidden] = useState<TrabajoForm | null>(null);
  const [saving, setSaving] = useState(false);
  const [statusMsg, setStatusMsg] = useState('');
  const [salidaActiva, setSalidaActiva] = useState(bootstrap.flags.salida_temporal_activa);
  const [salidaModalOpen, setSalidaModalOpen] = useState(false);
  const [entregaIdx, setEntregaIdx] = useState(0);
  const [entregaConfirmOpen, setEntregaConfirmOpen] = useState(false);
  const [entregaFirmasOpen, setEntregaFirmasOpen] = useState(false);
  const [clienteExpandido, setClienteExpandido] = useState(false);
  const [lockLost, setLockLost] = useState(false);

  const firmaClienteInicialRef = useRef<SignaturePadHandle | null>(null);
  const firmaTecnicoInicialRef = useRef<SignaturePadHandle | null>(null);
  const firmaClienteRef = useRef<SignaturePadHandle | null>(null);
  const firmaTecnicoRef = useRef<SignaturePadHandle | null>(null);
  const saveAfterSalidaRef = useRef(false);
  const clienteSectionRef = useRef<HTMLDivElement | null>(null);
  const permitirSalirRef = useRef(false);
  const formDirtyRef = useRef(false);
  const draftRestoreDoneRef = useRef(false);
  const draftSkipSaveRef = useRef(false);
  const lockLostRef = useRef(false);

  const marcarDirty = useCallback(() => {
    if (draftSkipSaveRef.current) return;
    formDirtyRef.current = true;
    setFormDirty(true);
  }, []);

  const limpiarDirty = useCallback(() => {
    formDirtyRef.current = false;
    setFormDirty(false);
  }, []);

  // Nav logout (and legacy scripts) call this so beforeunload does not cancel the POST.
  useEffect(() => {
    const permitir = () => {
      permitirSalirRef.current = true;
      formDirtyRef.current = false;
      setFormDirty(false);
    };
    window.anluxPermitirSalidaOrdenForm = permitir;

    // Capture-phase: allow logout even if nav forgot to call the hook.
    const onSubmitCapture = (e: Event) => {
      const form = e.target as HTMLFormElement | null;
      const action = String(form?.getAttribute?.('action') || form?.action || '');
      if (/\/logout\/?$/i.test(action) || action.includes('/logout')) {
        permitir();
      }
    };
    document.addEventListener('submit', onSubmitCapture, true);

    return () => {
      document.removeEventListener('submit', onSubmitCapture, true);
      if (window.anluxPermitirSalidaOrdenForm === permitir) {
        delete window.anluxPermitirSalidaOrdenForm;
      }
    };
  }, [bootstrap.meta.id_orden_c]);

  const modo = bootstrap.meta.modo;
  const readOnly = modo === 'solo_lectura' || salidaActiva || lockLost;
  const idOrden = bootstrap.meta.id_orden_c;
  const estatusEntregado = /entreg/i.test(cliente.estatus);
  const mostrarTaller =
    modo === 'completar'
    || modo === 'solo_lectura'
    || (modo === 'editar' && !/recep/i.test(cliente.estatus));
  const sersop01Info = useMemo(
    () => getSersop01(bootstrap.catalogs.servicios_sersop),
    [bootstrap.catalogs.servicios_sersop],
  );
  const showFirmasInicial = (modo === 'nueva' && !estatusEntregado) || (firmasReactivadas && modo !== 'completar');
  const showFirmasEntrega = estatusEntregado || (firmasReactivadas && modo === 'completar');
  const estatusKey = cliente.estatus
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .trim()
    .toLowerCase();
  const ordenEnProceso = estatusKey === 'en proceso' || estatusKey === 'proceso';
  const ordenTerminada = estatusKey === 'terminado';
  const showAcciones = idOrden > 0 && (ordenEnProceso || ordenTerminada);
  const puedeRegistrarSalida =
    idOrden > 0
    && ordenEnProceso
    && !salidaActiva
    && !readOnly
    && !!bootstrap.urls.salida_temporal;
  const showClienteSection = modo !== 'completar' || clienteExpandido;
  const salidaEquipo = equipos.find(
    (equipo) => Number(equipo.id_equipo) === Number(bootstrap.flags.salida_temporal_id_equipo || 0),
  );
  const totales = useMemo(
    () => calcTotalesFromForm(trabajos, materiales, anticipos, abonoSaldo),
    [trabajos, materiales, anticipos, abonoSaldo],
  );

  useEffect(() => {
    if (idOrden <= 0 || modo === 'solo_lectura') return;
    const url = bootstrap.urls.lock_heartbeat;
    if (!url) return;
    let timerId = 0;
    const avisarLockPerdido = async (holder?: string | null) => {
      if (lockLostRef.current) return;
      lockLostRef.current = true;
      setLockLost(true);
      if (timerId) {
        window.clearInterval(timerId);
        timerId = 0;
      }
      await showAlert(
        holder
          ? `Ya no tienes el bloqueo de esta orden (lo tiene: ${holder}). Se abrirá el listado.`
          : 'Ya no tienes el bloqueo de esta orden (otro usuario la tomó o expiró). Se abrirá el listado.',
        { title: 'Orden liberada', icon: 'warning' },
      );
      permitirSalirRef.current = true;
      limpiarDirty();
      window.location.href = bootstrap.urls.ordenes_index || '/ordenes';
    };
    const tick = () => {
      void lockHeartbeat(url, bootstrap.meta.csrf).then((res) => {
        if (res && res.success === false) {
          void avisarLockPerdido(res.holder_nombre);
        }
      });
    };
    tick();
    timerId = window.setInterval(tick, 30000);
    return () => {
      if (timerId) window.clearInterval(timerId);
    };
  }, [idOrden, modo, bootstrap.urls.lock_heartbeat, bootstrap.urls.ordenes_index, bootstrap.meta.csrf, showAlert, limpiarDirty]);

  // Si la orden ya viene liquidada (saldo ~0 con pagos), no repreguntar "¿Cliente pagó?".
  useEffect(() => {
    if (saldoPagadoConfirmadoFlag) return;
    if (Math.abs(totales.saldoPendiente) <= 0.009 && totales.anticiposSinIva + abonoSaldo > 0.009) {
      setSaldoPagadoConfirmadoFlag(true);
    }
  }, [totales.saldoPendiente, totales.anticiposSinIva, abonoSaldo, saldoPagadoConfirmadoFlag]);

  useEffect(() => {
    if (idOrden <= 0 || modo === 'solo_lectura') return;
    const url = bootstrap.urls.lock_release;
    if (!url) return;
    const release = () => {
      void lockRelease(url, bootstrap.meta.csrf);
    };
    window.addEventListener('beforeunload', release);
    window.addEventListener('pagehide', release);
    return () => {
      window.removeEventListener('beforeunload', release);
      window.removeEventListener('pagehide', release);
      release();
    };
  }, [idOrden, modo, bootstrap.urls.lock_release, bootstrap.meta.csrf]);

  // Dirty: marcar al cambiar datos del formulario (salta restauración de borrador).
  useEffect(() => {
    if (!draftRestoreDoneRef.current) return;
    marcarDirty();
  }, [
    cliente,
    equipos,
    observaciones,
    trabajos,
    materiales,
    anticipos,
    abonoSaldo,
    abonoSaldoEquipos,
    comentariosTecnico,
    marcarDirty,
  ]);

  // beforeunload: aviso de cambios + release de lock (lock ya tiene su propio listener).
  useEffect(() => {
    if (readOnly) return;
    const onBeforeUnload = (e: BeforeUnloadEvent) => {
      if (permitirSalirRef.current || !formDirtyRef.current) return;
      e.preventDefault();
      e.returnValue = '';
    };
    window.addEventListener('beforeunload', onBeforeUnload);
    return () => window.removeEventListener('beforeunload', onBeforeUnload);
  }, [readOnly]);

  // Click en links: confirmar salida si hay cambios.
  useEffect(() => {
    if (readOnly) return;
    const onClick = (e: MouseEvent) => {
      if (permitirSalirRef.current || !formDirtyRef.current) return;
      const t = e.target as HTMLElement | null;
      const a = t?.closest?.('a[href]') as HTMLAnchorElement | null;
      if (!a) return;
      const href = a.getAttribute('href') || '';
      if (!href || href.startsWith('#') || href.toLowerCase().startsWith('javascript:')) return;
      if (a.target === '_blank' || a.hasAttribute('download')) return;
      e.preventDefault();
      e.stopPropagation();
      void (async () => {
        const ok = await showConfirm(
          'Tienes datos sin guardar en esta orden. Si sales o recargas la página, se perderán.\n\n¿Quieres salir sin guardar?',
          {
            title: 'Cambios sin guardar',
            icon: 'warning',
            confirmText: 'Sí, salir',
            cancelText: 'Seguir editando',
          },
        );
        if (!ok) return;
        permitirSalirRef.current = true;
        limpiarDirty();
        window.location.href = a.href;
      })();
    };
    document.addEventListener('click', onClick, true);
    return () => document.removeEventListener('click', onClick, true);
  }, [readOnly, showConfirm, limpiarDirty]);

  // Borrador local: solo órdenes nuevas.
  useEffect(() => {
    if (readOnly || idOrden > 0) {
      draftRestoreDoneRef.current = true;
      return;
    }
    if (draftRestoreDoneRef.current) return;
    const key = borradorStorageKey(idOrden, modo);
    const draft = leerBorrador(key);
    if (!draft || !borradorTieneContenidoUtil(draft)) {
      draftRestoreDoneRef.current = true;
      return;
    }

    void (async () => {
      const ok = await showConfirm(
        `Se encontró un borrador local sin guardar (por ejemplo si la tablet salió del modo PC o se recargó la página).\n\n`
          + `Guardado: ${formatFechaBorrador(draft.savedAt)}.\n\n`
          + '¿Quieres recuperar esos datos?\n\nNota: el estatus de la orden no cambia al recuperar.',
        {
          title: 'Recuperar borrador',
          icon: 'warning',
          confirmText: 'Sí, recuperar',
          cancelText: 'No, descartar',
        },
      );
      if (!ok) {
        borrarBorrador(key);
        draftRestoreDoneRef.current = true;
        return;
      }
      draftSkipSaveRef.current = true;
      setCliente((prev) => ({
        ...prev,
        nombreCliente: String(draft.cab.nombre_cliente || prev.nombreCliente).toUpperCase(),
        direccion: String(draft.cab.direccion || prev.direccion).toUpperCase(),
        telefono: String(draft.cab.telefono || prev.telefono),
        correo: String(draft.cab.correo || prev.correo).toLowerCase(),
        poblacion: String(draft.cab.poblacion || prev.poblacion).toUpperCase(),
        folio: String(draft.cab.folio || prev.folio),
        fechaEntrada: String(draft.cab.fecha_entrada || prev.fechaEntrada),
        estatus: String(draft.cab.estatus || prev.estatus),
      }));
      if (draft.equipos?.length) {
        setEquipos(
          draft.equipos.map((eq) => ({
            ...emptyEquipo(),
            marca: String(eq.marca || '').toUpperCase(),
            modelo: String(eq.modelo || '').toUpperCase(),
            serie: String(eq.serie || '').toUpperCase(),
            descripcionFalla: String(eq.descripcion_falla || '').toUpperCase(),
            tipoServicio: String(eq.tipo_servicio || ''),
          })),
        );
      }
      if (draft.trabajos?.length) {
        setTrabajos(
          draft.trabajos.map((t) => ({
            clave: String(t.clave || ''),
            descripcion: String(t.descripcion || '').toUpperCase(),
            importe: t.importe == null ? '' : String(t.importe),
            ticket: String(t.ticket || '').toUpperCase(),
            id_equipo: t.id_equipo == null ? '' : String(t.id_equipo),
          })),
        );
      }
      if (draft.materiales?.length) {
        setMateriales(
          draft.materiales.map((m) => ({
            vale: String(m.vale || '').toUpperCase(),
            codigo: String(m.codigo || '').toUpperCase(),
            cant: String(m.cantidad ?? ''),
            descripcion: String(m.descripcion || '').toUpperCase(),
            precio: String(m.precio_unitario ?? ''),
            ticket: String(m.ticket || '').toUpperCase(),
            id_equipo: m.id_equipo == null ? '' : String(m.id_equipo),
          })),
        );
      }
      if (draft.anticipos?.length) {
        setAnticipos(
          draft.anticipos.map((a) => ({
            folio: String(a.folio || '').toUpperCase(),
            descripcion: String(a.descripcion || '').toUpperCase(),
            monto: a.monto == null ? '' : String(a.monto),
            ticket: String(a.ticket || '').toUpperCase(),
            id_equipo: a.id_equipo == null ? '' : String(a.id_equipo),
          })),
        );
      }
      if (draft.observaciones_items?.length) setObservaciones(draft.observaciones_items.map(String));
      if (draft.comentarios_tecnico != null) setComentariosTecnico(String(draft.comentarios_tecnico).toUpperCase());
      setAbonoSaldo(Number(draft.abono_saldo) || 0);
      if (draft.abono_saldo_equipos) setAbonoSaldoEquipos(draft.abono_saldo_equipos);
      setSaldoPagadoConfirmadoFlag(String(draft.saldo_pagado_confirmado) === '1');
      if (draft.sersop01 && draft.sersop01.clave) {
        setSersop01Hidden({
          clave: String(draft.sersop01.clave),
          descripcion: String(draft.sersop01.descripcion || '').toUpperCase(),
          importe: String(draft.sersop01.importe || ''),
          ticket: String(draft.sersop01.ticket || ''),
          id_equipo: String(draft.sersop01.id_equipo || ''),
        });
      }
      const firmas = draft.firmas || { firma_c_e: '', firma_t_r: '', firma_c_r: '', firma_t_e: '' };
      window.setTimeout(() => {
        void (async () => {
          if (firmas.firma_c_e) await firmaClienteInicialRef.current?.loadFromDataUrl(firmas.firma_c_e);
          if (firmas.firma_t_r) await firmaTecnicoInicialRef.current?.loadFromDataUrl(firmas.firma_t_r);
          if (firmas.firma_c_r) await firmaClienteRef.current?.loadFromDataUrl(firmas.firma_c_r);
          if (firmas.firma_t_e) await firmaTecnicoRef.current?.loadFromDataUrl(firmas.firma_t_e);
          draftSkipSaveRef.current = false;
          limpiarDirty();
          draftRestoreDoneRef.current = true;
          setDraftBanner(`Borrador local recuperado (${formatHoraBorrador(draft.savedAt)}).`);
        })();
      }, 100);
    })();
  }, [idOrden, modo, readOnly, showConfirm, limpiarDirty]);

  // Autosave borrador (debounce 700ms) solo nueva + dirty.
  useEffect(() => {
    if (readOnly || idOrden > 0 || !formDirty) return;
    const key = borradorStorageKey(idOrden, modo);
    const timer = window.setTimeout(() => {
      if (draftSkipSaveRef.current) return;
      const draft = recolectarBorrador({
        idOrden,
        cliente,
        equipos,
        trabajos,
        materiales,
        anticipos,
        observaciones,
        abonoSaldo,
        abonoSaldoEquipos,
        saldoPagadoConfirmado: saldoPagadoConfirmadoFlag,
        comentariosTecnico,
        sersop01: sersop01Hidden,
        firmas: {
          firma_c_e: firmaClienteInicialRef.current?.hasStroke()
            ? firmaClienteInicialRef.current.getDataUrl()
            : '',
          firma_t_r: firmaTecnicoInicialRef.current?.hasStroke()
            ? firmaTecnicoInicialRef.current.getDataUrl()
            : '',
          firma_c_r: firmaClienteRef.current?.hasStroke() ? firmaClienteRef.current.getDataUrl() : '',
          firma_t_e: firmaTecnicoRef.current?.hasStroke() ? firmaTecnicoRef.current.getDataUrl() : '',
        },
      });
      if (!borradorTieneContenidoUtil(draft)) return;
      if (guardarBorrador(key, draft)) {
        setDraftBanner(`Borrador local guardado a las ${formatHoraBorrador(draft.savedAt)}.`);
      } else {
        setDraftBanner('No se pudo guardar el borrador local (¿almacenamiento lleno?).');
      }
    }, 700);

    const flush = () => {
      if (draftSkipSaveRef.current || !formDirtyRef.current) return;
      const draft = recolectarBorrador({
        idOrden,
        cliente,
        equipos,
        trabajos,
        materiales,
        anticipos,
        observaciones,
        abonoSaldo,
        abonoSaldoEquipos,
        saldoPagadoConfirmado: saldoPagadoConfirmadoFlag,
        comentariosTecnico,
        sersop01: sersop01Hidden,
        firmas: {
          firma_c_e: firmaClienteInicialRef.current?.hasStroke()
            ? firmaClienteInicialRef.current.getDataUrl()
            : '',
          firma_t_r: firmaTecnicoInicialRef.current?.hasStroke()
            ? firmaTecnicoInicialRef.current.getDataUrl()
            : '',
          firma_c_r: firmaClienteRef.current?.hasStroke() ? firmaClienteRef.current.getDataUrl() : '',
          firma_t_e: firmaTecnicoRef.current?.hasStroke() ? firmaTecnicoRef.current.getDataUrl() : '',
        },
      });
      if (borradorTieneContenidoUtil(draft)) guardarBorrador(key, draft);
    };
    const onVis = () => {
      if (document.visibilityState === 'hidden') flush();
    };
    window.addEventListener('pagehide', flush);
    document.addEventListener('visibilitychange', onVis);
    window.addEventListener('orientationchange', flush);

    return () => {
      window.clearTimeout(timer);
      window.removeEventListener('pagehide', flush);
      document.removeEventListener('visibilitychange', onVis);
      window.removeEventListener('orientationchange', flush);
    };
  }, [
    readOnly,
    idOrden,
    modo,
    formDirty,
    cliente,
    equipos,
    trabajos,
    materiales,
    anticipos,
    observaciones,
    abonoSaldo,
    abonoSaldoEquipos,
    saldoPagadoConfirmadoFlag,
    comentariosTecnico,
    sersop01Hidden,
  ]);

  const appendTrabajosMaterialesAnticipos = useCallback(
    (fd: FormData, trabajosSrc: TrabajoForm[], mats: MaterialForm[], ants: AnticipoForm[]) => {
      trabajosSrc.forEach((t, i) => {
        fd.append(`trabajos[${i}][clave]`, t.clave);
        fd.append(`trabajos[${i}][descripcion]`, t.descripcion);
        fd.append(`trabajos[${i}][importe]`, t.importe);
        fd.append(`trabajos[${i}][ticket]`, t.ticket);
        fd.append(`trabajos[${i}][id_equipo]`, t.id_equipo);
      });
      mats.forEach((m, i) => {
        fd.append(`materiales[${i}][vale]`, m.vale);
        fd.append(`materiales[${i}][codigo]`, m.codigo);
        fd.append(`materiales[${i}][cant]`, m.cant);
        fd.append(`materiales[${i}][descripcion]`, m.descripcion);
        fd.append(`materiales[${i}][precio]`, m.precio);
        fd.append(`materiales[${i}][ticket]`, m.ticket);
        fd.append(`materiales[${i}][id_equipo]`, m.id_equipo);
      });
      ants.forEach((a, i) => {
        fd.append(`anticipos[${i}][folio]`, a.folio);
        fd.append(`anticipos[${i}][descripcion]`, a.descripcion);
        fd.append(`anticipos[${i}][monto]`, a.monto);
        fd.append(`anticipos[${i}][ticket]`, a.ticket);
        fd.append(`anticipos[${i}][id_equipo]`, a.id_equipo);
      });
    },
    [],
  );

  const buildFormData = useCallback((opts?: {
    estatusOverride?: string;
    entregaPorEquipo?: boolean;
    equipoIndice?: number;
    idEquipo?: number;
    accionesOverride?: Record<number, number>;
    recibidoCliente?: string;
    entregaQuienRecibe?: string;
    firmaClienteOverride?: string;
    firmaTecnicoOverride?: string;
    abonoOverride?: number;
    sersopHidden?: TrabajoForm | null;
    /** Solo tras confirmación explícita del técnico (paridad legacy). */
    saldoPagadoConfirmado?: boolean;
    permitirNegativos?: boolean;
    permitirSaldoNegativo?: boolean;
  }): FormData => {
    const fd = new FormData();
    fd.append('_token', bootstrap.meta.csrf);
    if (idOrden > 0) fd.append('id_orden_c', String(idOrden));
    fd.append('modo_completar', modo === 'completar' ? '1' : '0');
    fd.append('folio', cliente.folio);
    fd.append('nombreCliente', cliente.nombreCliente);
    fd.append('direccion', cliente.direccion);
    fd.append('telefono', cliente.telefono);
    fd.append('correo', String(cliente.correo || '').trim().toLowerCase());
    fd.append('poblacion', cliente.poblacion);
    fd.append('fechaEntrada', cliente.fechaEntrada);
    fd.append('estatus', opts?.estatusOverride || cliente.estatus);
    fd.append('comentariosTecnico', comentariosTecnico);

    observaciones
      .map((o) => o.trim())
      .filter(Boolean)
      .forEach((o) => fd.append('observaciones[]', o));

    equipos.forEach((eq, i) => {
      const acciones = opts?.accionesOverride?.[i] ?? (eq.acciones || 0);
      fd.append(`equipos[${i}][marca]`, eq.marca);
      fd.append(`equipos[${i}][modelo]`, eq.modelo);
      fd.append(`equipos[${i}][serie]`, eq.serie);
      fd.append(`equipos[${i}][descripcionFalla]`, eq.descripcionFalla);
      fd.append(`equipos[${i}][tipoServicio]`, eq.tipoServicio);
      fd.append(`equipos[${i}][acciones]`, String(acciones));
      if (eq.id_equipo) fd.append(`equipos[${i}][id_equipo]`, String(eq.id_equipo));
    });

    const sersop = opts?.sersopHidden ?? sersop01Hidden;
    const trabajosOut =
      mostrarTaller
        ? trabajos
        : sersop
          ? [sersop]
          : [];
    const matsOut = mostrarTaller ? materiales : [];
    const antsOut = mostrarTaller ? anticipos : [];
    if (trabajosOut.length || matsOut.length || antsOut.length) {
      appendTrabajosMaterialesAnticipos(fd, trabajosOut, matsOut, antsOut);
    }

    const abono = opts?.abonoOverride != null ? opts.abonoOverride : abonoSaldo;
    fd.append('abono_saldo', String(abono || 0));
    fd.append('abono_saldo_equipos', JSON.stringify(abonoSaldoEquipos || {}));
    if (opts?.permitirNegativos) {
      fd.append('permitir_negativos', '1');
    }
    if (opts?.permitirSaldoNegativo) {
      fd.append('permitir_saldo_negativo', '1');
    }
    if (opts?.saldoPagadoConfirmado || saldoPagadoConfirmadoFlag) {
      fd.append('saldo_pagado_confirmado', '1');
    }

    if (opts?.entregaPorEquipo) {
      fd.append('entrega_por_equipo', '1');
      fd.append('equipo_indice', String(opts.equipoIndice || 0));
      fd.append('id_equipo', String(opts.idEquipo || opts.equipoIndice || 0));
      if (opts.recibidoCliente) fd.append('recibido_cliente', opts.recibidoCliente);
      if (opts.entregaQuienRecibe) fd.append('entrega_quien_recibe', opts.entregaQuienRecibe);
    }

    if (firmasOff) {
      fd.append('firmaClienteInicial', FIRMA_PNG_VACIA);
      fd.append('firmaTecnicoInicial', FIRMA_PNG_VACIA);
      fd.append('firmaCliente', opts?.firmaClienteOverride || FIRMA_PNG_VACIA);
      fd.append('firmaTecnico', opts?.firmaTecnicoOverride || FIRMA_PNG_VACIA);
    } else {
      if (showFirmasInicial) {
        fd.append('firmaClienteInicial', firmaClienteInicialRef.current?.getDataUrl() || FIRMA_PNG_VACIA);
        fd.append('firmaTecnicoInicial', firmaTecnicoInicialRef.current?.getDataUrl() || FIRMA_PNG_VACIA);
      } else {
        fd.append('firmaClienteInicial', FIRMA_PNG_VACIA);
        fd.append('firmaTecnicoInicial', FIRMA_PNG_VACIA);
      }
      if (opts?.firmaClienteOverride || opts?.firmaTecnicoOverride) {
        fd.append('firmaCliente', opts.firmaClienteOverride || FIRMA_PNG_VACIA);
        fd.append('firmaTecnico', opts.firmaTecnicoOverride || FIRMA_PNG_VACIA);
      } else if (showFirmasEntrega) {
        fd.append('firmaCliente', firmaClienteRef.current?.getDataUrl() || FIRMA_PNG_VACIA);
        fd.append('firmaTecnico', firmaTecnicoRef.current?.getDataUrl() || FIRMA_PNG_VACIA);
      } else {
        fd.append('firmaCliente', FIRMA_PNG_VACIA);
        fd.append('firmaTecnico', FIRMA_PNG_VACIA);
      }
    }

    return fd;
  }, [
    bootstrap.meta.csrf,
    idOrden,
    modo,
    cliente,
    observaciones,
    equipos,
    trabajos,
    materiales,
    anticipos,
    abonoSaldo,
    abonoSaldoEquipos,
    saldoPagadoConfirmadoFlag,
    comentariosTecnico,
    mostrarTaller,
    firmasOff,
    showFirmasInicial,
    showFirmasEntrega,
    sersop01Hidden,
    appendTrabajosMaterialesAnticipos,
  ]);

  const afterSaveSuccess = async (data: {
    idOrden?: number;
    id_orden_c?: number;
    message?: string;
    email_notice?: string;
    email_notice_level?: string;
    whatsapp_notice?: string;
    whatsapp_notice_level?: string;
    whatsapp_notification_id?: number | null;
    whatsapp_applicable?: boolean;
  }, salidaPayload: SalidaTemporalPayload | null) => {
    const idGuardada = Number(data.idOrden || data.id_orden_c || idOrden || 0);
    permitirSalirRef.current = true;
    limpiarDirty();
    borrarBorrador(borradorStorageKey(idOrden, modo));
    setDraftBanner('');
    let waNotice = data.whatsapp_notice;
    let waLevel = String(data.whatsapp_notice_level || '').toLowerCase();
    let waStatus = '';
    const waId = Number(data.whatsapp_notification_id || 0);
    if (waId > 0 && waLevel !== 'error') {
      setStatusMsg('Confirmando entrega de la plantilla de WhatsApp...');
      const root = bootstrap.urls.ordenes_index.replace(/\/ordenes\/?$/, '') || window.location.origin;
      const result = await waitWhatsappSettled(root, waId, bootstrap.meta.csrf);
      waNotice = result.message;
      waLevel = result.level;
      waStatus = result.status;
    }

    const correoLevel = String(data.email_notice_level || '').toLowerCase();
    const hayError = correoLevel === 'error' || waLevel === 'error';
    const hayWarn = correoLevel === 'warning' || waLevel === 'warning' || correoLevel === 'warn' || waLevel === 'warn';
    const partes = [
      `Orden: GUARDADA.\n${String(data.message || 'La orden de servicio se guardó correctamente.')}`,
      emailStatusText(data.email_notice, correoLevel),
      whatsappStatusText(waNotice, waLevel, waStatus),
    ];
    await showAlert(partes.join('\n\n'), {
      title: hayError ? 'Orden guardada con envíos fallidos' : hayWarn ? 'Orden guardada; falta confirmación' : 'Orden y notificaciones procesadas',
      icon: hayError ? 'error' : hayWarn ? 'warning' : 'success',
    });

    if (salidaPayload && idGuardada > 0 && bootstrap.urls.salida_temporal) {
      setStatusMsg('Registrando salida temporal...');
      const st = await salidaTemporal(
        bootstrap.urls.salida_temporal.includes('{id}')
          ? bootstrap.urls.salida_temporal.replace('{id}', String(idGuardada))
          : bootstrap.urls.salida_temporal.replace(/\/\d+(\/salida-temporal)?$/, `/${idGuardada}/salida-temporal`),
        salidaPayload,
        bootstrap.meta.csrf,
      );
      if (!st.success) {
        await showAlert(st.message || 'Orden guardada, pero fallo la salida temporal.', {
          title: 'Salida temporal',
          icon: 'warning',
        });
      } else {
        setSalidaActiva(true);
      }
    }

    if (idGuardada > 0 && bootstrap.urls.pdf) {
      const pdfUrl = bootstrap.urls.pdf.includes('{id}')
        ? bootstrap.urls.pdf.replace('{id}', String(idGuardada))
        : bootstrap.urls.pdf.replace(/\/\d+(\?|$)/, `/${idGuardada}$1`);
      void abrirPdfOrden(pdfUrl);
    }

    if (modo === 'editar' || modo === 'completar') {
      window.location.reload();
    } else {
      window.location.href = bootstrap.urls.ordenes_index || '/ordenes';
    }
  };

  const prepareSersop01IfNeeded = async (): Promise<{ ok: boolean; hidden: TrabajoForm | null; abonoExtra: number }> => {
    if (modo !== 'nueva') return { ok: true, hidden: null, abonoExtra: 0 };
    const yaTiene = trabajos.some((t) => String(t.clave || '').trim().toUpperCase() === 'SERSOP01' && (Number(t.importe) || 0) > 0);
    if (yaTiene) return { ok: true, hidden: null, abonoExtra: 0 };

    const servicio = getSersop01(bootstrap.catalogs.servicios_sersop);
    const precioSinIva = Number(servicio.precio || 603.45);
    const precioConIva = anluxMontoConIva(precioSinIva);
    const clientePago = await showConfirm(
      `SERSOP01 — ${servicio.descripcion}\n`
        + `$${precioSinIva.toFixed(2)} sin IVA ($${precioConIva.toFixed(2)} con IVA).\n\n`
        + `¿El cliente ya pagó este cobro de revisión?\n\n`
        + `• Aceptar: se pedirá ticket/factura y se registrará SERSOP01.\n`
        + `• Cancelar: no se registrará SERSOP01.`,
      {
        title: 'Cobro por defecto en esta orden nueva',
        icon: 'warning',
        confirmText: 'Sí, ya pagó',
        cancelText: 'No registrar SERSOP01',
      },
    );
    if (!clientePago) return { ok: true, hidden: null, abonoExtra: 0 };

    const ticket = await showPrompt('Captura el ticket / factura del pago de SERSOP01:', {
      title: 'Ticket / factura',
      confirmText: 'Registrar',
      cancelText: 'Cancelar',
      placeholder: 'Ej. TICKET-001',
    });
    if (ticket === null) {
      await showAlert('Guardado cancelado. Sin ticket/factura no se registra el cobro SERSOP01.', {
        title: 'Operación cancelada',
        icon: 'warning',
      });
      return { ok: false, hidden: null, abonoExtra: 0 };
    }
    const ticketNorm = String(ticket || '').trim().toUpperCase();
    if (!ticketNorm) {
      await showAlert('Debes capturar ticket o factura para registrar SERSOP01.', {
        title: 'Dato requerido',
        icon: 'warning',
      });
      return { ok: false, hidden: null, abonoExtra: 0 };
    }
    return {
      ok: true,
      hidden: {
        clave: 'SERSOP01',
        descripcion: String(servicio.descripcion || 'REVISION').toUpperCase(),
        importe: String(precioSinIva),
        ticket: ticketNorm,
        id_equipo: '',
      },
      abonoExtra: precioSinIva,
    };
  };

  const runSave = async (salidaPayload: SalidaTemporalPayload | null) => {
    if (readOnly) return;
    if (saving) {
      await showAlert('La orden ya se está guardando. Espera a que termine el envío.', {
        title: 'Guardando',
        icon: 'info',
      });
      return;
    }
    flushAllNetoInputs();
    if (!cliente.nombreCliente.trim() && modo !== 'completar') {
      await showAlert('Captura el nombre del cliente.', { title: 'Dato requerido', icon: 'warning' });
      focusAnluxField('cliente.nombreCliente');
      return;
    }
    if (!cliente.poblacion.trim() && modo !== 'completar') {
      await showAlert('Captura la poblacion/ciudad.', { title: 'Dato requerido', icon: 'warning' });
      focusAnluxField('cliente.poblacion');
      return;
    }

    const vEq = validarEquipos(equipos);
    if (!vEq.ok) {
      await showAlert(vEq.message, { title: vEq.title || 'Faltan datos en la orden', icon: 'warning' });
      focusAnluxField(vEq.focus);
      return;
    }

    // Observaciones: bloquear pronto en orden nueva (antes de firmas / SERSOP / pagos).
    const vObsEarly = validarObservaciones(modo, idOrden, observaciones);
    if (!vObsEarly.ok) {
      await showAlert(vObsEarly.message, { title: vObsEarly.title || 'Observaciones', icon: 'warning' });
      focusAnluxField(vObsEarly.focus);
      return;
    }

    if (!firmasOff) {
      if (showFirmasInicial) {
        if (!firmaClienteInicialRef.current?.hasStroke() || !firmaTecnicoInicialRef.current?.hasStroke()) {
          await showAlert('Debes firmar en Cliente y Tecnico (firmas iniciales).', {
            title: 'Firmas requeridas',
            icon: 'warning',
          });
          return;
        }
      }
      if (showFirmasEntrega) {
        if (!firmaClienteRef.current?.hasStroke() || !firmaTecnicoRef.current?.hasStroke()) {
          await showAlert('Debes firmar en Cliente y Tecnico (firmas de entrega).', {
            title: 'Firmas requeridas',
            icon: 'warning',
          });
          return;
        }
      }
    }

    const sersopPrep = await prepareSersop01IfNeeded();
    if (!sersopPrep.ok) return;
    if (sersopPrep.hidden) setSersop01Hidden(sersopPrep.hidden);

    const trabajosCalc = mostrarTaller
      ? trabajos
      : sersopPrep.hidden
        ? [sersopPrep.hidden]
        : sersop01Hidden
          ? [sersop01Hidden]
          : [];
    const matsCalc = mostrarTaller ? materiales : [];
    const antsCalc = mostrarTaller ? anticipos : [];
    let abonoWorking = abonoSaldo + (sersopPrep.abonoExtra || 0);

    if (mostrarTaller) {
      const vSer = validarServiciosExtra(trabajos, bootstrap.catalogs.servicios_sersop);
      if (!vSer.ok) {
        await showAlert(vSer.message, { title: vSer.title || 'Faltan datos en la orden', icon: 'warning' });
        focusAnluxField(vSer.focus);
        return;
      }
      const vMat = validarMateriales(materiales);
      if (!vMat.ok) {
        await showAlert(vMat.message, { title: vMat.title || 'Faltan datos en la orden', icon: 'warning' });
        focusAnluxField(vMat.focus);
        return;
      }
      const vAnt = validarAnticipos(anticipos);
      if (!vAnt.ok) {
        await showAlert(vAnt.message, { title: vAnt.title || 'Faltan datos en la orden', icon: 'warning' });
        focusAnluxField(vAnt.focus);
        return;
      }
    }

    const totPre = calcTotalesFromForm(trabajosCalc.length ? trabajosCalc : trabajos, matsCalc, antsCalc, abonoWorking);

    const vEnt = validarEntregadoLiquidado(cliente.estatus, totPre.saldoPendiente);
    if (!vEnt.ok) {
      await showAlert(vEnt.message, { title: vEnt.title || 'Saldo pendiente', icon: 'warning' });
      return;
    }

    const saldoConfirm = await confirmarSaldoAlGuardar(
      { showAlert, showConfirm },
      {
        total: totPre.total,
        saldoPendiente: totPre.saldoPendiente,
        anticiposSinIva: totPre.anticiposSinIva,
        abonoSaldo: abonoWorking,
        yaConfirmado: saldoPagadoConfirmadoFlag,
      },
    );
    if (!saldoConfirm.ok) return;
    abonoWorking = saldoConfirm.abonoFinal;
    if (Math.abs(abonoWorking - abonoSaldo) > 0.0001) {
      setAbonoSaldo(abonoWorking);
    }
    if (saldoConfirm.saldoPagadoConfirmado) {
      setSaldoPagadoConfirmadoFlag(true);
    }

    const trabajosNeg = mostrarTaller ? trabajos : trabajosCalc;
    const matsNeg = matsCalc.length ? matsCalc : materiales;
    const antsNeg = antsCalc.length ? antsCalc : anticipos;
    const tieneNegativos = hayNumerosNegativos(trabajosNeg, matsNeg, antsNeg);
    if (tieneNegativos) {
      const okNeg = await showConfirm(
        'La orden contiene números negativos. ¿Deseas aceptar y guardar la orden así?',
        {
          title: 'Confirmar importes',
          icon: 'warning',
          confirmText: 'Sí, guardar',
          cancelText: 'Revisar',
        },
      );
      if (!okNeg) return;
    }

    const totPost = calcTotalesFromForm(
      trabajosCalc.length ? trabajosCalc : trabajos,
      matsCalc,
      antsCalc,
      abonoWorking,
    );
    let permitirSaldoNegativo = false;
    if (totPost.saldoPendiente < -0.009) {
      const okSaldoNeg = await showConfirm(
        'El saldo pendiente queda en número negativo. ¿Deseas aceptar y guardar la orden así?',
        {
          title: 'Confirmar saldo negativo',
          icon: 'warning',
          confirmText: 'Sí, guardar',
          cancelText: 'Revisar',
        },
      );
      if (!okSaldoNeg) return;
      permitirSaldoNegativo = true;
    }

    const vPob = validarPoblacion(cliente.poblacion, modo);
    if (!vPob.ok) {
      await showAlert(vPob.message, { title: vPob.title || 'Faltan datos en la orden', icon: 'warning' });
      focusAnluxField(vPob.focus);
      return;
    }
    const correoNorm = String(cliente.correo || '').trim().toLowerCase();
    if (correoNorm !== '' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correoNorm)) {
      await showAlert('Correo electrónico no válido.', { title: 'Faltan datos en la orden', icon: 'warning' });
      focusAnluxField('cliente.correo');
      return;
    }
    // Normalizar población (paridad Exacto).
    const poblacionNorm = cliente.poblacion.trim().replace(/\s+/g, ' ');
    if (poblacionNorm !== cliente.poblacion || correoNorm !== cliente.correo) {
      setCliente((c) => ({ ...c, poblacion: poblacionNorm, correo: correoNorm }));
    }

    setSaving(true);
    setStatusMsg('Guardando orden...');
    try {
      const data = await registrarOrden(
        bootstrap.urls.registrar,
        buildFormData({
          sersopHidden: sersopPrep.hidden,
          abonoOverride: abonoWorking,
          saldoPagadoConfirmado: saldoConfirm.saldoPagadoConfirmado || saldoPagadoConfirmadoFlag,
          permitirNegativos: tieneNegativos,
          permitirSaldoNegativo,
        }),
        bootstrap.meta.csrf,
      );
      if (!data.success) {
        if (data.processing || data.duplicate_submit) {
          await showAlert(data.message || 'La orden ya se está guardando. Espera a que termine el envío.', {
            title: 'Guardando',
            icon: 'info',
          });
          setStatusMsg(data.message || 'Guardado en proceso...');
          return;
        }
        await showAlert(data.message || 'No se pudo guardar la orden.', {
          title: 'No se pudo guardar',
          icon: 'error',
        });
        setStatusMsg(data.message || 'Error al guardar');
        return;
      }
      await afterSaveSuccess(data, salidaPayload);
    } catch (err) {
      const msg = err instanceof Error ? err.message : 'Error de red';
      await showAlert(`Error al guardar: ${msg}`, { title: 'Error', icon: 'error' });
      setStatusMsg(msg);
    } finally {
      setSaving(false);
    }
  };

  const onSave = async () => {
    if (readOnly) return;
    const ofrecerSalida =
      idOrden > 0
      && !salidaActiva
      && cliente.estatus === 'En proceso'
      && !!bootstrap.urls.salida_temporal;

    if (ofrecerSalida) {
      const quiereSalida = await showConfirm(
        '¿Deseas registrar una salida temporal del equipo despues de guardar?',
        {
          title: 'Salida temporal',
          icon: 'warning',
          confirmText: 'Sí, registrar salida',
          cancelText: 'Solo guardar',
        },
      );
      if (quiereSalida) {
        saveAfterSalidaRef.current = true;
        setSalidaModalOpen(true);
        return;
      }
    }
    await runSave(null);
  };

  const onSalidaModalConfirm = (payload: SalidaTemporalPayload) => {
    setSalidaModalOpen(false);
    if (saveAfterSalidaRef.current) {
      saveAfterSalidaRef.current = false;
      void runSave(payload);
    }
  };

  const onRegreso = async () => {
    if (!bootstrap.urls.regreso_temporal) return;
    const ok = await showConfirm('¿Registrar el regreso del equipo al taller?', {
      title: 'Regreso al taller',
      icon: 'warning',
      confirmText: 'Sí, registrar',
      cancelText: 'Cancelar',
    });
    if (!ok) return;
    try {
      const res = await regresoTemporal(bootstrap.urls.regreso_temporal, bootstrap.meta.csrf);
      await showAlert(res.message || (res.success ? 'Regreso registrado.' : 'No se pudo registrar el regreso.'), {
        title: res.success ? 'Listo' : 'Error',
        icon: res.success ? 'success' : 'error',
      });
      if (res.success) {
        setSalidaActiva(false);
        window.location.reload();
      }
    } catch (err) {
      await showAlert(err instanceof Error ? err.message : 'Error de red', { title: 'Error', icon: 'error' });
    }
  };

  const onReenviar = async () => {
    if (!bootstrap.urls.reenviar) return;
    try {
      setStatusMsg('Reenviando WhatsApp y correo...');
      const res = await reenviarRecepcion(bootstrap.urls.reenviar, bootstrap.meta.csrf, {
        nombreCliente: cliente.nombreCliente,
        telefono: cliente.telefono,
        correo: cliente.correo,
        direccion: cliente.direccion,
        poblacion: cliente.poblacion,
      });
      const correoLevel = String(res.email_notice_level || '').toLowerCase();
      const waLevel = String(res.whatsapp_notice_level || '').toLowerCase();
      const hayError = !res.success || correoLevel === 'error' || waLevel === 'error';
      const partes = [res.message, res.email_notice, res.whatsapp_notice].filter(Boolean);
      await showAlert(partes.join('\n\n') || (res.success ? 'Reenvio procesado.' : 'No se pudo reenviar.'), {
        title: hayError ? 'No se pudo reenviar' : 'Listo',
        icon: hayError ? 'error' : 'success',
      });
      setStatusMsg('');
    } catch (err) {
      await showAlert(err instanceof Error ? err.message : 'Error de red', { title: 'Error', icon: 'error' });
      setStatusMsg('');
    }
  };

  const onLiquidarSaldo = () => {
    if (totales.saldoPendiente <= 0.009) {
      void showAlert('No hay saldo pendiente por pagar.', { title: 'Saldo pendiente', icon: 'info' });
      return;
    }
    setLiquidarOpen(true);
  };

  const onReactivarFirmas = async () => {
    const ok = await showConfirm(
      'Se limpiarán los campos capturados (excepto folio, fecha y estatus) y se activarán las firmas.\n\n¿Continuar?',
      {
        title: 'Volver a llenar',
        icon: 'warning',
        confirmText: 'Sí, limpiar y activar',
        cancelText: 'Cancelar',
      },
    );
    if (!ok) return;

    const folio = cliente.folio;
    const fechaEntrada = cliente.fechaEntrada;
    const estatus = cliente.estatus;
    setFirmasOff(false);
    setFirmasReactivadas(true);
    setCliente({
      nombreCliente: '',
      direccion: '',
      telefono: '',
      correo: '',
      poblacion: '',
      folio,
      fechaEntrada,
      estatus,
    });
    setEquipos([emptyEquipo()]);
    setObservaciones(['']);
    setTrabajos([emptyTrabajo()]);
    setMateriales([emptyMaterial()]);
    setAnticipos([emptyAnticipo()]);
    setComentariosTecnico('');
    setAbonoSaldo(0);
    setAbonoSaldoEquipos({});
    setSersop01Hidden(null);
    setSaldoPagadoConfirmadoFlag(false);
    window.setTimeout(() => {
      if (modo === 'completar') {
        firmaClienteRef.current?.clear();
        firmaTecnicoRef.current?.clear();
      } else {
        firmaClienteInicialRef.current?.clear();
        firmaTecnicoInicialRef.current?.clear();
        firmaClienteRef.current?.clear();
        firmaTecnicoRef.current?.clear();
      }
      marcarDirty();
    }, 50);
  };

  const equipoEntrega = entregaIdx > 0 ? equipos[entregaIdx - 1] || null : null;

  const guardarEntregaTerminado = async () => {
    if (!equipoEntrega || entregaIdx < 1) return;
    setSaving(true);
    setStatusMsg('Marcando equipo como Terminado...');
    try {
      const data = await registrarOrden(
        bootstrap.urls.registrar,
        buildFormData({
          estatusOverride: 'Terminado',
          entregaPorEquipo: true,
          equipoIndice: entregaIdx,
          idEquipo: Number(equipoEntrega.id_equipo) || entregaIdx,
          accionesOverride: { [entregaIdx - 1]: 1 },
        }),
        bootstrap.meta.csrf,
      );
      if (!data.success) {
        await showAlert(data.message || 'No se pudo marcar Terminado.', { title: 'Error', icon: 'error' });
        return;
      }
      setEquipos((prev) => prev.map((eq, i) => (i === entregaIdx - 1 ? { ...eq, acciones: 1 } : eq)));
      setEntregaConfirmOpen(false);
      await showAlert(data.message || 'Equipo marcado como Terminado.', { title: 'Listo', icon: 'success' });
      window.location.reload();
    } catch (err) {
      await showAlert(err instanceof Error ? err.message : 'Error de red', { title: 'Error', icon: 'error' });
    } finally {
      setSaving(false);
    }
  };

  const guardarEntregaFirmas = async (payload: {
    recibidoCliente?: string;
    entregaQuienRecibe?: 'cliente' | 'tercero';
    firmaCliente?: string;
    firmaTecnico?: string;
  }) => {
    if (!equipoEntrega || entregaIdx < 1) return;
    setSaving(true);
    setStatusMsg('Guardando entrega del equipo...');
    try {
      const data = await registrarOrden(
        bootstrap.urls.registrar,
        buildFormData({
          estatusOverride: 'Entregado',
          entregaPorEquipo: true,
          equipoIndice: entregaIdx,
          idEquipo: Number(equipoEntrega.id_equipo) || entregaIdx,
          accionesOverride: { [entregaIdx - 1]: 2 },
          recibidoCliente: payload.recibidoCliente,
          entregaQuienRecibe: payload.entregaQuienRecibe,
          firmaClienteOverride: payload.firmaCliente,
          firmaTecnicoOverride: payload.firmaTecnico,
        }),
        bootstrap.meta.csrf,
      );
      if (!data.success) {
        await showAlert(data.message || 'No se pudo guardar la entrega.', { title: 'Error', icon: 'error' });
        return;
      }
      await afterSaveSuccess(data, null);
    } catch (err) {
      await showAlert(err instanceof Error ? err.message : 'Error de red', { title: 'Error', icon: 'error' });
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="anlux-page-card">
      <header className="anlux-page-header">
        <div>
          <p className="anlux-eyebrow">Anlux · Orden de servicio</p>
          <h1 className="anlux-page-title">
            {modo === 'nueva' ? 'Nueva orden de servicio' : `Orden ${cliente.folio || ''}`}
          </h1>
          <p className="anlux-page-description">Captura cliente, equipos, servicio, firmas y cobros sin perder el contexto.</p>
        </div>
        <span className="inline-flex min-h-11 items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-4 text-sm font-semibold text-blue-900">
          <i className="fas fa-user-cog" aria-hidden="true" />
          {bootstrap.meta.nombre_tecnico || 'Técnico sin asignar'}
        </span>
      </header>
      <div className="space-y-5 p-4 sm:p-6">
      {draftBanner ? (
        <div className="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-900">
          {draftBanner}
        </div>
      ) : null}
      {modo === 'solo_lectura' ? (
        <div className="mb-4 rounded-lg border-2 border-emerald-500 bg-emerald-50 px-4 py-3 text-sm text-emerald-950 shadow-sm">
          <p className="text-base font-bold">
            <i className="fas fa-eye mr-2 text-emerald-700" />
            Orden entregada — solo lectura
          </p>
          <p className="mt-1">Puedes revisar toda la orden. No se puede editar ni guardar cambios.</p>
        </div>
      ) : null}

      {salidaActiva && modo !== 'solo_lectura' ? (
        <div
          className="mb-4 flex flex-col gap-3 rounded-lg px-4 py-3 text-sm shadow-sm sm:flex-row sm:items-center sm:justify-between"
          style={{ border: '2px solid #fb923c', background: '#fff7ed', color: '#7c2d12' }}
        >
          <div className="min-w-0 flex-1">
            <p className="text-base font-bold" style={{ color: '#9a3412', margin: 0 }}>
              <i className="fas fa-truck-loading mr-2" style={{ color: '#ea580c' }} />
              Equipo en salida temporal
            </p>
            <p className="mt-1" style={{ margin: '0.35rem 0 0', color: '#7c2d12' }}>
              {bootstrap.flags.fecha_salida_temporal ? (
                <>
                  Salida:
                  {' '}
                  <strong>{bootstrap.flags.fecha_salida_temporal}</strong>
                  .
                  {' '}
                </>
              ) : null}
              El estatus sigue en
              {' '}
              <strong>En proceso</strong>
              . Registra el regreso cuando el cliente vuelva a dejar el equipo.
            </p>
            {bootstrap.flags.motivo_salida_temporal ? (
              <p className="mt-2 text-xs sm:text-sm" style={{ margin: '0.5rem 0 0', color: '#7c2d12' }}>
                <strong>Motivo:</strong>
                {' '}
                {bootstrap.flags.motivo_salida_temporal}
              </p>
            ) : null}
            {salidaEquipo ? (
              <p className="mt-2 text-xs sm:text-sm" style={{ margin: '0.5rem 0 0', color: '#7c2d12' }}>
                <strong>Equipo fuera:</strong>{' '}
                {[salidaEquipo.marca, salidaEquipo.modelo, salidaEquipo.serie].filter(Boolean).join(' · ') || `Equipo #${salidaEquipo.id_equipo}`}
              </p>
            ) : null}
          </div>
          <button
            type="button"
            onClick={() => void onRegreso()}
            className="shrink-0 rounded-lg px-4 py-2.5 text-sm font-bold shadow-sm"
            style={{ border: '2px solid #c2410c', background: '#ea580c', color: '#fff' }}
          >
            <i className="fas fa-undo mr-2" />
            Registrar regreso
          </button>
        </div>
      ) : null}

      {puedeRegistrarSalida ? (
        <section className="mb-4 flex flex-col gap-3 rounded-xl border border-orange-300 bg-orange-50 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <p className="font-bold text-orange-900">
              <i className="fas fa-truck-loading mr-2" aria-hidden="true" />
              Salida temporal
            </p>
            <p className="mt-1 text-sm text-orange-800">
              Registra el motivo y las firmas cuando el cliente retire temporalmente un equipo sin cerrar la orden.
            </p>
          </div>
          <button
            type="button"
            disabled={saving}
            onClick={() => {
              saveAfterSalidaRef.current = true;
              setSalidaModalOpen(true);
            }}
            className="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-lg bg-orange-600 px-5 text-sm font-bold text-white shadow-sm transition hover:bg-orange-700 disabled:opacity-60"
          >
            <i className="fas fa-sign-out-alt" aria-hidden="true" />
            Registrar salida temporal
          </button>
        </section>
      ) : null}

      {modo === 'completar' ? (
        <div className="mb-6 rounded-lg border-2 border-blue-600 bg-blue-50 p-5 shadow-sm">
          <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div className="min-w-0 flex-1 text-left uppercase">
              <p className="text-base font-bold text-blue-900">Orden ya registrada en la tabla de ordenes</p>
              <p className="mt-1 text-sm font-semibold text-blue-800">
                Folio
                {' '}
                <strong>{cliente.folio}</strong>
                {' '}
                · Cliente
                {' '}
                <strong>{cliente.nombreCliente}</strong>
                {' '}
                · Estatus
                {' '}
                <strong>{cliente.estatus.toUpperCase()}</strong>
              </p>
              <p className="mt-2 text-xs font-semibold normal-case text-blue-700">
                Los datos del cliente se visualizan al editar o al imprimir el documento.
              </p>
              <div className="mt-3 flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
                <button
                  type="button"
                  id="btnEditarDatosCliente"
                  onClick={() => {
                    setClienteExpandido(true);
                    window.setTimeout(() => {
                      clienteSectionRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                      const nombre = clienteSectionRef.current?.querySelector<HTMLInputElement>('input:not([readonly])');
                      try {
                        nombre?.focus();
                      } catch {
                        /* ignore */
                      }
                    }, 50);
                  }}
                  className="inline-flex items-center justify-center gap-2 rounded-lg border-2 border-blue-600 bg-white px-4 py-2 text-sm font-bold text-blue-800 shadow-sm hover:bg-blue-50"
                >
                  <i className="fas fa-user-edit" />
                  Editar datos del cliente
                </button>
                <button
                  type="button"
                  onClick={() => void onReenviar()}
                  className="inline-flex items-center justify-center gap-2 rounded-lg border-2 px-4 py-2 text-sm font-bold shadow-sm"
                  style={{ backgroundColor: '#059669', color: '#ffffff', borderColor: '#047857' }}
                >
                  <i className="fab fa-whatsapp" />
                  Reenviar WhatsApp/correo
                </button>
              </div>
              <p className="mt-2 text-[11px] font-medium normal-case leading-snug text-blue-700">
                Usa «Reenviar» si el WhatsApp o el correo no llegaron (numero o correo equivocado). Se envia de nuevo como recepcion.
              </p>
            </div>
            <div className="w-full shrink-0 lg:w-56">
              <label className="mb-2 block text-sm font-bold uppercase text-blue-900" htmlFor="estatusOrdenBanner">
                ESTATUS
              </label>
              <EstatusSelect
                id="estatusOrdenBanner"
                value={cliente.estatus}
                options={bootstrap.catalogs.estatus_flujo}
                disabled={readOnly}
                onChange={(estatus) => setCliente((prev) => ({ ...prev, estatus }))}
              />
            </div>
          </div>
        </div>
      ) : null}

      <p className="text-sm text-blue-800">
        <i className="fas fa-user-cog mr-2" />
        Tecnico:
        {' '}
        <strong>{bootstrap.meta.nombre_tecnico || '—'}</strong>
      </p>

      {modo === 'editar' || modo === 'solo_lectura' ? (
        <div className="mb-4 max-w-xs">
          <label className="mb-2 block text-sm font-bold uppercase text-blue-900" htmlFor="estatusOrdenExterno">
            ESTATUS
          </label>
          <EstatusSelect
            id="estatusOrdenExterno"
            value={cliente.estatus}
            options={bootstrap.catalogs.estatus_flujo}
            disabled={readOnly}
            onChange={(estatus) => setCliente((prev) => ({ ...prev, estatus }))}
          />
        </div>
      ) : null}

      {showClienteSection ? (
        <div ref={clienteSectionRef} id="seccionDatosCliente">
          <ClienteSection
            value={cliente}
            readOnly={readOnly}
            onChange={setCliente}
          />
        </div>
      ) : null}

      <EquiposSection
        equipos={equipos}
        tiposServicio={bootstrap.catalogs.tipos_servicio}
        readOnly={readOnly}
        showAcciones={showAcciones}
        onChange={setEquipos}
        onEntregarEquipo={(idx) => {
          setEntregaIdx(idx);
          setEntregaConfirmOpen(true);
        }}
      />

      {bootstrap.catalogs.condiciones_entrega.length > 0 ? (
        <section className="rounded-r-lg border-l-4 border-blue-500 bg-blue-50 p-4 sm:pl-6">
          <h2 className="mb-6 flex items-center text-xl font-bold text-blue-900 sm:text-2xl">
            <i className="fas fa-info-circle mr-3 text-blue-500" />
            CONDICIONES DE ENTREGA DEL EQUIPO
          </h2>
          <div className="space-y-2 text-left text-xs font-normal uppercase italic leading-relaxed text-blue-900 sm:text-sm">
            {bootstrap.catalogs.condiciones_entrega.map((linea) => (
              <p key={linea} className="mb-0">
                •
                {' '}
                {linea}
              </p>
            ))}
          </div>
        </section>
      ) : null}

      {modo !== 'completar' ? (
        <ObservacionesSection
          observaciones={observaciones}
          readOnly={readOnly}
          requerido={modo === 'nueva'}
          onChange={setObservaciones}
        />
      ) : null}

      <FirmasSection
        showInicial={showFirmasInicial}
        showEntrega={showFirmasEntrega}
        disabled={readOnly}
        firmasDeshabilitadas={firmasOff}
        onReactivarFirmas={() => void onReactivarFirmas()}
        onDirty={marcarDirty}
        firmaClienteInicialRef={firmaClienteInicialRef}
        firmaTecnicoInicialRef={firmaTecnicoInicialRef}
        firmaClienteRef={firmaClienteRef}
        firmaTecnicoRef={firmaTecnicoRef}
        preload={bootstrap.orden?.firmas}
      />

      {modo === 'nueva' && !mostrarTaller ? (
        <div className="rounded-lg border-2 border-amber-400 bg-amber-50 p-4 text-left shadow-sm">
          <p className="text-base font-bold uppercase text-amber-950">
            <i className="fas fa-receipt mr-2 text-amber-600" />
            Cobro por defecto en orden nueva
          </p>
          <p className="mt-2 text-sm font-semibold uppercase text-amber-900">
            Se cobra
            {' '}
            <strong>SERSOP01</strong>
            {' '}
            —
            {' '}
            {sersop01Info.descripcion}
            {' '}
            —
            {' '}
            <strong>
              $
              {Number(sersop01Info.precio || 0).toFixed(2)}
              {' '}
              sin IVA
            </strong>
            {' '}
            ($
            {anluxMontoConIva(Number(sersop01Info.precio || 0)).toFixed(2)}
            {' '}
            con IVA).
          </p>
          <ul className="mt-2 list-disc space-y-1 pl-5 text-sm text-amber-900">
            <li>Al guardar, el sistema preguntará si el cliente ya pagó este cobro.</li>
            <li>
              <strong>Si no pagó:</strong>
              {' '}
              no se registra nada de SERSOP01.
            </li>
            <li>
              <strong>Si pagó:</strong>
              {' '}
              pedirá ticket/factura y sí se registra el cobro (sale en trabajos del PDF y en el subtotal).
            </li>
          </ul>
        </div>
      ) : null}

      {mostrarTaller ? (
        <>
          <TrabajosSection
            trabajos={trabajos}
            serviciosSersop={bootstrap.catalogs.servicios_sersop}
            numEquipos={equipos.length}
            readOnly={readOnly}
            onChange={setTrabajos}
          />
          <ComentariosTecnicoSection
            comentarios={comentariosTecnico}
            readOnly={readOnly}
            titulo={modo === 'completar' ? 'COMENTARIOS' : 'COMENTARIOS DEL TECNICO'}
            onChange={setComentariosTecnico}
          />
          <MaterialesSection
            materiales={materiales}
            numEquipos={equipos.length}
            readOnly={readOnly}
            onChange={setMateriales}
          />
          <AnticiposSection
            anticipos={anticipos}
            numEquipos={equipos.length}
            readOnly={readOnly}
            onChange={setAnticipos}
          />
          <TotalesBar
            trabajos={trabajos}
            materiales={materiales}
            anticipos={anticipos}
            abonoSaldo={abonoSaldo}
            readOnly={readOnly}
            onAbonoSaldoChange={setAbonoSaldo}
            onLiquidarSaldo={onLiquidarSaldo}
          />
        </>
      ) : null}

      <div className="mt-10 flex flex-col items-center gap-4 border-t-2 border-blue-300 pt-8">
        {statusMsg ? (
          <div className="w-full max-w-2xl rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-center text-sm text-blue-900">
            {saving ? <i className="fas fa-spinner fa-spin mr-2" /> : null}
            {statusMsg}
          </div>
        ) : null}

        {modo === 'solo_lectura' ? (
          <div className="flex flex-wrap justify-center gap-3">
            <a
              href={bootstrap.urls.ordenes_index || '/ordenes'}
              className="inline-flex items-center justify-center rounded-lg border-2 border-blue-600 bg-white px-8 py-3 font-bold text-blue-800 hover:bg-blue-50"
            >
              <i className="fas fa-arrow-left mr-2" />
              Volver a ordenes
            </a>
            {bootstrap.urls.pdf ? (
              <button
                type="button"
                className="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-8 py-3 font-bold text-white hover:bg-indigo-700"
                onClick={() => void abrirPdfOrden(bootstrap.urls.pdf)}
              >
                <i className="fas fa-file-pdf mr-2" />
                Ver PDF
              </button>
            ) : null}
          </div>
        ) : (
          <>
            {!readOnly && Math.abs(totales.saldoPendiente) > 0.009 && mostrarTaller ? (
              <button
                type="button"
                disabled={saving}
                onClick={() => onLiquidarSaldo()}
                className="inline-flex items-center justify-center rounded-lg bg-red-600 px-6 py-3 font-bold text-white shadow-sm hover:bg-red-700 disabled:opacity-60"
              >
                <i className="fas fa-cash-register mr-2" />
                Liquidar Saldo por Equipo
              </button>
            ) : null}
            <button
              type="button"
              disabled={saving || readOnly}
              onClick={() => void onSave()}
              className="flex w-full items-center justify-center rounded-lg bg-blue-600 px-8 py-3 font-bold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto"
            >
              <i className="fas fa-save mr-2" />
              {modo === 'completar' ? 'Guardar datos tecnicos' : 'Guardar Orden de Servicio'}
            </button>
          </>
        )}
      </div>

      <SalidaTemporalModal
        open={salidaModalOpen}
        busy={saving}
        equipos={equipos}
        onCancel={() => {
          setSalidaModalOpen(false);
          saveAfterSalidaRef.current = false;
        }}
        onConfirm={onSalidaModalConfirm}
      />

      <EntregaConfirmModal
        open={entregaConfirmOpen}
        equipo={equipoEntrega}
        equipoIndice={entregaIdx}
        saldoPendiente={totales.saldoPendiente}
        busy={saving}
        onCancel={() => setEntregaConfirmOpen(false)}
        onLiquidar={() => {
          onLiquidarSaldo();
        }}
        onConfirmTerminado={() => void guardarEntregaTerminado()}
        onNeedFirmas={() => {
          setEntregaConfirmOpen(false);
          setEntregaFirmasOpen(true);
        }}
      />

      <LiquidarSaldoModal
        open={liquidarOpen}
        equipos={equipos}
        trabajos={trabajos}
        materiales={materiales}
        anticipos={anticipos}
        abonoMap={abonoSaldoEquipos}
        saldoPendienteOrden={totales.saldoPendiente}
        abonoSaldoActual={abonoSaldo}
        onClose={() => setLiquidarOpen(false)}
        onApplied={({ abonoSaldo: nextAbono, abonoMap, saldoPagadoConfirmado }) => {
          setAbonoSaldo(nextAbono);
          setAbonoSaldoEquipos(abonoMap);
          if (saldoPagadoConfirmado) setSaldoPagadoConfirmadoFlag(true);
          marcarDirty();
        }}
      />

      <EntregaFirmasModal
        open={entregaFirmasOpen}
        equipo={equipoEntrega}
        nombreClienteTitular={cliente.nombreCliente}
        busy={saving}
        onCancel={() => {
          setEntregaFirmasOpen(false);
          setEntregaConfirmOpen(true);
        }}
        onConfirm={(payload) => void guardarEntregaFirmas(payload)}
      />
      </div>
    </div>
  );
}
