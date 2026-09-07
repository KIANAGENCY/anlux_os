# Inventario legacy → React/TS (`orden_servicio`)

Fuente canónica: `public/legacy/assets/js/orden_servicio.js` (~5500 líneas)  
Firmas entrega: `public/legacy/assets/js/orden_firmas_entrega.js`  
Vista: `resources/views/orders/orden_form.blade.php` (shell React + `#orden-react-root`)

## APIs (no cambiar contrato)

| Método | URL | Uso |
|--------|-----|-----|
| POST | `/api/ordenes/registrar` | Guardar / actualizar orden |
| POST | `/api/ordenes/{id}/salida-temporal` | Salida temporal |
| POST | `/api/ordenes/{id}/regreso-temporal` | Regreso |
| POST | `/api/ordenes/{id}/lock/heartbeat` | Lock edición |
| POST | `/api/ordenes/{id}/lock/release` | Liberar lock |
| GET | `/api/ordenes/whatsapp-estado/{id}` | Poll WhatsApp |
| POST | `/api/ordenes/{id}/reenviar` | Reenviar recepción |
| POST | `/api/ordenes/validar-saldo` | Validar saldo |
| POST | `/api/ordenes/liquidar-saldo-equipo` | API stub (no usada por UI React; liquidación = abono_saldo + mapa equipos + guardar) |
| GET | `/pdf/orden/{id}` | PDF |

## UI React (`App.tsx` + sections) — estado

| Sección | Archivo | Estado |
|---------|---------|--------|
| Datos cliente | `components/ClienteSection.tsx` | ✅ |
| Equipos (add/remove + truck) | `components/EquiposSection.tsx` | ✅ |
| Observaciones dinámicas | `components/ObservacionesSection.tsx` | ✅ |
| Firmas canvas + reactivar | `components/SignaturePad.tsx` + `FirmasSection.tsx` | ✅ |
| Trabajos SERSOP | `components/TrabajosSection.tsx` | ✅ |
| Materiales + neto c/IVA | `components/MaterialesSection.tsx` + `NetoIvaNumberInput.tsx` | ✅ |
| Anticipos + neto c/IVA | `components/AnticiposSection.tsx` | ✅ |
| Totales / IVA / saldo | `components/TotalesBar.tsx` | ✅ |
| Liquidar saldo por equipo | `components/LiquidarSaldoModal.tsx` | ✅ |
| Comentarios del técnico | `components/ComentariosTecnicoSection.tsx` | ✅ |
| Reenviar con datos cliente | `api.ts` + `App.tsx` | ✅ |
| Validaciones al guardar + foco | `lib/validaciones.ts` + `lib/focusField.ts` | ✅ |
| Dirty-exit + borrador local v3 | `lib/ordenDraft.ts` + `App.tsx` | ✅ |
| Neto c/IVA + flush al guardar | `NetoIvaNumberInput` + `lib/netoFlush.ts` | ✅ |
| Firmas dirty + reactivar/reset | `SignaturePad` + `FirmasSection` | ✅ |
| Modal salida temporal + firmas | `components/SalidaTemporalModal.tsx` | ✅ |
| Entrega por equipo (Terminado/Entregado + firmas) | `components/EntregaEquipoModals.tsx` | ✅ |
| SERSOP01 cobro orden nueva | `App.tsx` | ✅ |
| Poll WhatsApp post-guardar | `api.ts` + `App.tsx` | ✅ |
| Guardar + FormData RegistrarOrdenService | `App.tsx` | ✅ |
| Lock heartbeat 30s + beforeunload release | `App.tsx` | ✅ |
| DialogProvider (alerts/confirms) | `shared/nav/ui.tsx` | ✅ |

## Cómo validar

Crear orden (SERSOP01), editar, firmar, Terminado→Entregado por equipo, PDF, salida temporal + regreso, liquidar por equipo, borrador local en nueva, validaciones al guardar.
