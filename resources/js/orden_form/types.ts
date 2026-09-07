export type OrdenModo = 'nueva' | 'editar' | 'completar' | 'solo_lectura';

export type ServicioSersop = {
  clave: string;
  descripcion: string;
  precio: number;
  editable?: boolean | number | string;
};

export type EquipoForm = {
  id_equipo?: number | null;
  marca: string;
  modelo: string;
  serie: string;
  descripcionFalla: string;
  tipoServicio: string;
  acciones: number;
  entrega_receptor_tipo?: string | null;
  entrega_recibido_cliente?: string | null;
  entrega_fecha?: string | null;
  entrega_tecnico?: string | null;
};

export type TrabajoForm = {
  clave: string;
  descripcion: string;
  importe: string;
  ticket: string;
  id_equipo: string;
};

export type MaterialForm = {
  vale: string;
  codigo: string;
  cant: string;
  descripcion: string;
  precio: string;
  ticket: string;
  id_equipo: string;
};

export type AnticipoForm = {
  folio: string;
  descripcion: string;
  monto: string;
  ticket: string;
  id_equipo: string;
};

export type FirmasUrls = {
  firma_c_e?: string | null;
  firma_t_r?: string | null;
  firma_c_r?: string | null;
  firma_t_e?: string | null;
  firma_c_salida_temp?: string | null;
  firma_t_salida_temp?: string | null;
};

export type OrdenBootstrap = {
  id_orden_c: number;
  cab: Record<string, unknown>;
  t: Record<string, unknown> | null;
  equipos: Array<Record<string, unknown>>;
  trabajos: Array<Record<string, unknown>>;
  materiales: Array<Record<string, unknown>>;
  anticipos: Array<Record<string, unknown>>;
  abono_saldo: number;
  observaciones_items: string[];
  firmas: FirmasUrls;
  salida_temporal?: {
    activa?: boolean;
    fecha_salida?: string | null;
    fecha_regreso?: string | null;
    motivo?: string;
  };
};

export type OrdenFormBootstrap = {
  meta: {
    id_orden_c: number;
    modo: OrdenModo;
    folio_preview: string;
    nombre_tecnico: string;
    firmas_deshabilitadas: boolean;
    csrf: string;
  };
  urls: {
    registrar: string;
    reenviar: string;
    salida_temporal: string;
    regreso_temporal: string;
    lock_heartbeat: string;
    lock_release: string;
    ordenes_index: string;
    pdf: string;
  };
  catalogs: {
    tipos_servicio: string[];
    servicios_sersop: ServicioSersop[];
    condiciones_entrega: string[];
    estatus_flujo: string[];
  };
  flags: {
    salida_temporal_activa: boolean;
    motivo_salida_temporal: string;
    fecha_salida_temporal: string;
    salida_temporal_id_equipo?: number;
  };
  orden: OrdenBootstrap | null;
};

export type ClienteState = {
  nombreCliente: string;
  direccion: string;
  telefono: string;
  correo: string;
  poblacion: string;
  folio: string;
  fechaEntrada: string;
  estatus: string;
};

export type FormState = {
  cliente: ClienteState;
  equipos: EquipoForm[];
  observaciones: string[];
  trabajos: TrabajoForm[];
  materiales: MaterialForm[];
  anticipos: AnticipoForm[];
  abono_saldo: number;
};

export type RegistrarResponse = {
  success?: boolean;
  message?: string;
  idOrden?: number;
  id_orden_c?: number;
  folio?: string;
  processing?: boolean;
  duplicate_submit?: boolean;
  ask_salida_temporal?: boolean;
  email_notice?: string;
  email_notice_level?: string;
  whatsapp_notice?: string;
  whatsapp_notice_level?: string;
  whatsapp_applicable?: boolean;
  whatsapp_notification_id?: number | null;
};

export type WhatsappEstadoResponse = {
  success?: boolean;
  status?: string;
  level?: string;
  message?: string;
  settled?: boolean;
};

export type SalidaTemporalPayload = {
  id_equipo: number;
  motivo: string;
  firma_cliente: string;
  firma_tecnico: string;
};

export type EntregaEquipoPayload = {
  equipoIndice: number;
  idEquipo: number;
  estatus: 'Terminado' | 'Entregado';
  acciones: number;
  recibidoCliente?: string;
  entregaQuienRecibe?: 'cliente' | 'tercero';
  firmaCliente?: string;
  firmaTecnico?: string;
};

export type ApiOkResponse = {
  success?: boolean;
  message?: string;
  holder_nombre?: string | null;
  email_notice?: string;
  email_notice_level?: string;
  whatsapp_notice?: string;
  whatsapp_notice_level?: string;
};

export const FIRMA_PNG_VACIA =
  'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

export type SignaturePadHandle = {
  clear: () => void;
  loadFromDataUrl: (dataUrl: string | null | undefined) => Promise<void>;
  getDataUrl: () => string;
  hasStroke: () => boolean;
};
