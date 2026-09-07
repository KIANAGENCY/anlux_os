export type EstatusFiltro = 'todos' | 'rojo' | 'naranja' | 'amarillo' | 'verde';
export type EstatusRadio = 'rojo' | 'naranja' | 'amarillo';
export type SortOrdenes = 'fecha' | 'estatus';

export interface OrdenListItem {
  id_orden_c: number | string;
  folio?: string | null;
  nombre_cliente?: string | null;
  fecha_entrada?: string | null;
  fecha_terminada?: string | null;
  fecha_salida?: string | null;
  fecha_salida_temporal?: string | null;
  salida_temporal_activa?: number | string | boolean | null;
  estatus?: string | null;
  estatus_label?: string | null;
  tecnico_recibido?: string | null;
  tecnicos_log?: string | null;
  TECNICOS_LOG?: string | null;
  entregado_por_tecnico?: string | null;
  involucrados_display?: string | null;
  firmas_recepcion_ok?: number | string | boolean | null;
  edit_lock_active?: boolean | number | string | null;
  edit_lock_is_mine?: boolean | number | string | null;
  edit_lock_nombre?: string | null;
}

export interface OrdenesPagination {
  page: number;
  perPage: number;
  total: number;
  totalPages: number;
}

export interface OrdenesListResponse {
  success: boolean;
  data?: OrdenListItem[] | Record<string, OrdenListItem>;
  pagination?: OrdenesPagination;
  message?: string;
}

export interface UpdateStatusResponse {
  success: boolean;
  message?: string;
}
