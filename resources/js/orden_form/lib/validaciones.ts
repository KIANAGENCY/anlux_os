import type {
  AnticipoForm,
  EquipoForm,
  MaterialForm,
  OrdenModo,
  ServicioSersop,
  TrabajoForm,
} from '../types';

export type ValidacionResultado =
  | { ok: true }
  | { ok: false; message: string; title?: string; focus?: string };

const TITLE_FALTAN = 'Faltan datos en la orden';

function servicioEditable(servicio: ServicioSersop | undefined, clave: string): boolean {
  if (servicio && (servicio.editable === true || servicio.editable === 1 || servicio.editable === '1')) {
    return true;
  }
  return String(clave || '').trim().toUpperCase() === 'SERSOPSA';
}

function findServicio(catalog: ServicioSersop[], clave: string): ServicioSersop | undefined {
  const up = String(clave || '').trim().toUpperCase();
  return catalog.find((s) => String(s.clave || '').trim().toUpperCase() === up);
}

export function validarEquipos(equipos: EquipoForm[]): ValidacionResultado {
  const requeridos: Array<[keyof EquipoForm, string]> = [
    ['marca', 'la marca'],
    ['modelo', 'el modelo o descripción'],
    ['serie', 'el número de serie'],
    ['descripcionFalla', 'la descripción de falla'],
    ['tipoServicio', 'el tipo de servicio'],
  ];
  let filasCompletas = 0;

  for (let i = 0; i < equipos.length; i++) {
    const eq = equipos[i];
    const valores: Record<string, string> = {};
    let conAlgo = false;
    for (const [clave] of requeridos) {
      const valor = String(eq[clave] ?? '').trim();
      valores[clave] = valor;
      if (valor !== '') conAlgo = true;
    }
    if (!conAlgo) continue;

    const filaNum = i + 1;
    for (const [clave, etiqueta] of requeridos) {
      if (valores[clave] === '') {
        return {
          ok: false,
          title: TITLE_FALTAN,
          message: `Equipo (fila ${filaNum}): falta ${etiqueta}. Completa todos los campos de la fila.`,
          focus: `equipo.${i}.${clave}`,
        };
      }
    }
    filasCompletas += 1;
  }

  if (filasCompletas === 0) {
    return {
      ok: false,
      title: TITLE_FALTAN,
      message:
        'Captura al menos un equipo con todos sus campos: marca, modelo, número de serie, descripción de falla y tipo de servicio.',
      focus: 'equipo.0.marca',
    };
  }
  return { ok: true };
}

export function validarServiciosExtra(
  trabajos: TrabajoForm[],
  catalog: ServicioSersop[],
): ValidacionResultado {
  for (let i = 0; i < trabajos.length; i++) {
    const t = trabajos[i];
    const clave = String(t.clave || '').trim();
    const descripcion = String(t.descripcion || '').trim();
    const importeStr = String(t.importe || '').trim();
    if (!clave && !descripcion && !importeStr) continue;

    const servicio = findServicio(catalog, clave);
    const claveUp = clave.toUpperCase();
    if (!servicio && claveUp !== 'SERSOPSA') continue;

    if (!servicioEditable(servicio, clave)) continue;

    const filaNum = i + 1;
    if (descripcion === '') {
      return {
        ok: false,
        title: TITLE_FALTAN,
        message: `Trabajo (fila ${filaNum}, SERVICIO EXTRA): captura la descripción.`,
        focus: `trabajo.${i}.descripcion`,
      };
    }
    const precio = Number.parseFloat(importeStr || '0');
    if (importeStr === '' || !Number.isFinite(precio)) {
      return {
        ok: false,
        title: TITLE_FALTAN,
        message: `Trabajo (fila ${filaNum}, SERVICIO EXTRA): captura un precio sin IVA válido.`,
        focus: `trabajo.${i}.importe`,
      };
    }
  }
  return { ok: true };
}

function materialUsado(m: MaterialForm): boolean {
  return [m.vale, m.codigo, m.cant, m.descripcion, m.precio].some((v) => String(v || '').trim() !== '');
}

export function validarMateriales(materiales: MaterialForm[]): ValidacionResultado {
  for (let i = 0; i < materiales.length; i++) {
    const m = materiales[i];
    if (!materialUsado(m)) continue;
    const filaNum = i + 1;
    if (!String(m.vale || '').trim()) {
      return { ok: false, title: TITLE_FALTAN, message: `Material (fila ${filaNum}): falta el vale.`, focus: `material.${i}.vale` };
    }
    if (!String(m.cant || '').trim()) {
      return { ok: false, title: TITLE_FALTAN, message: `Material (fila ${filaNum}): falta la cantidad.`, focus: `material.${i}.cant` };
    }
    if (!String(m.descripcion || '').trim()) {
      return { ok: false, title: TITLE_FALTAN, message: `Material (fila ${filaNum}): falta la descripción.`, focus: `material.${i}.descripcion` };
    }
    if (!String(m.precio || '').trim()) {
      return { ok: false, title: TITLE_FALTAN, message: `Material (fila ${filaNum}): falta el precio unitario.`, focus: `material.${i}.precio` };
    }
    if ((parseFloat(m.cant) || 0) < 0) {
      return {
        ok: false,
        title: TITLE_FALTAN,
        message: `Material (fila ${filaNum}): la cantidad no puede ser negativa.`,
        focus: `material.${i}.cant`,
      };
    }
    if ((parseFloat(m.precio) || 0) < 0) {
      return {
        ok: false,
        title: TITLE_FALTAN,
        message: `Material (fila ${filaNum}): el precio unitario no puede ser negativo.`,
        focus: `material.${i}.precio`,
      };
    }
  }
  return { ok: true };
}

export function validarAnticipos(anticipos: AnticipoForm[]): ValidacionResultado {
  for (let i = 0; i < anticipos.length; i++) {
    const a = anticipos[i];
    const folioTexto = String(a.folio || '').trim();
    const descTexto = String(a.descripcion || '').trim();
    const montoTexto = String(a.monto || '').trim();
    const ticketTexto = String(a.ticket || '').trim();
    const montoNum = montoTexto === '' ? NaN : parseFloat(montoTexto);
    const filaUsada =
      folioTexto !== ''
      || descTexto !== ''
      || ticketTexto !== ''
      || (montoTexto !== '' && (!Number.isFinite(montoNum) || Math.abs(montoNum) >= 0.009));
    if (!filaUsada) continue;

    const filaNum = i + 1;
    if (folioTexto === '') {
      return {
        ok: false,
        title: TITLE_FALTAN,
        message: `Anticipo (fila ${filaNum}): captura el folio del pedido.`,
        focus: `anticipo.${i}.folio`,
      };
    }
    if (descTexto === '') {
      return {
        ok: false,
        title: TITLE_FALTAN,
        message: `Anticipo (fila ${filaNum}): captura la descripción de refacción.`,
        focus: `anticipo.${i}.descripcion`,
      };
    }
    if (montoTexto === '') {
      return {
        ok: false,
        title: TITLE_FALTAN,
        message: `Anticipo (fila ${filaNum}): captura el monto pagado (puede ser 0).`,
        focus: `anticipo.${i}.monto`,
      };
    }
    if (!Number.isFinite(parseFloat(montoTexto))) {
      return {
        ok: false,
        title: TITLE_FALTAN,
        message: `Anticipo (fila ${filaNum}): el monto pagado no es válido.`,
        focus: `anticipo.${i}.monto`,
      };
    }
  }
  return { ok: true };
}

export function validarEntregadoLiquidado(estatus: string, saldoPendiente: number): ValidacionResultado {
  if (!/entreg/i.test(String(estatus || ''))) return { ok: true };
  if (Math.abs(saldoPendiente) > 0.009) {
    return {
      ok: false,
      title: 'Saldo pendiente',
      message: 'Para marcar como Entregado, el saldo pendiente debe quedar liquidado en $0.00.',
    };
  }
  return { ok: true };
}

export function validarObservaciones(
  modo: OrdenModo,
  idOrden: number,
  observaciones: string[],
): ValidacionResultado {
  // Obligatorio solo al capturar orden nueva (paridad Exacto: id vacío y no completar).
  if (modo !== 'nueva' || idOrden > 0) return { ok: true };
  const tieneTexto = observaciones.some((o) => String(o || '').trim() !== '');
  if (tieneTexto) return { ok: true };
  return {
    ok: false,
    title: 'Observaciones',
    message: 'Atención: el campo observaciones está vacío. Escriba al menos una observación.',
    focus: 'observacion.0',
  };
}

export function validarPoblacion(poblacionRaw: string, modo: OrdenModo): ValidacionResultado {
  const poblacion = String(poblacionRaw || '').trim().replace(/\s+/g, ' ');
  if (modo === 'completar' && poblacion === '') return { ok: true };
  if (
    poblacion.length < 2
    || poblacion.length > 80
    || /\d{4,}/.test(poblacion)
    || !/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s.'-]+$/.test(poblacion)
  ) {
    return {
      ok: false,
      title: TITLE_FALTAN,
      message:
        'Población/Ciudad: usa solo letras, espacios, puntos, apóstrofes o guiones (sin números largos).',
      focus: 'cliente.poblacion',
    };
  }
  return { ok: true };
}
