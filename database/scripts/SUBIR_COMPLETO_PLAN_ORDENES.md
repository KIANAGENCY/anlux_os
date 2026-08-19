# EXACTO — Subida completa (plan órdenes)

## Ya está en tu proyecto (sube tal cual)

```
app/Support/OrderStatus.php
app/Support/TipoServicioCatalog.php
app/Support/OrdenObservacionesValidator.php
app/Support/OrdenComentariosValidator.php
app/Services/OrdenListService.php
app/Services/RegistrarOrdenService.php          ← ver parche IVA abajo
app/Http/Controllers/OrderFormController.php
app/Http/Controllers/OrderPdfController.php
app/Services/OrdenPolicyService.php           ← si no está en servidor
app/Support/ExactoAuthContext.php
app/Services/ExactoVaultService.php
app/Services/OrdenStatusService.php
app/Models/User.php

public/legacy/assets/js/orden_servicio.js     ← ver parche IVA abajo
public/legacy/js/historial_laravel.js
public/legacy/js/ordenes_laravel.js

resources/views/orders/historial_page.blade.php
resources/views/orders/orden_form.blade.php   ← ver parche label IVA abajo
```

**Borrar en servidor si existe:** `public/docheck.php`

**Después de subir:** Ctrl+F5 en `/ordenes`, `/historial` y al abrir una orden.

---

## Parches IVA — aplicados en local (2026-05-20)

## ~~Falta aplicar 2 parches~~ (ya aplicados)

### 1) `public/legacy/assets/js/orden_servicio.js`

Busca la función `calcularTotalFactura` (~línea 2095) y **reemplázala** por:

```javascript
        function exactoEsEdicionOrden() {
            return Number(document.getElementById('id_orden_c')?.value || 0) > 0;
        }

        function calcularTotalFactura() {
            const elSt = document.getElementById('subtotalTrabajos');
            const elSm = document.getElementById('subtotalMateriales');
            const elIva = document.getElementById('iva');
            const elTot = document.getElementById('total');
            const elIvaLabel = document.getElementById('labelIva');
            if (!elSt || !elSm || !elIva || !elTot) return;
            const subtotalTrabajos = parseFloat(elSt.textContent) || 0;
            const subtotalMateriales = parseFloat(elSm.textContent) || 0;
            const esEdicion = exactoEsEdicionOrden();
            const baseIva = esEdicion ? subtotalMateriales : (subtotalTrabajos + subtotalMateriales);
            const iva = baseIva * 0.16;
            elIva.textContent = iva.toFixed(2);
            const total = subtotalTrabajos + subtotalMateriales + iva;
            elTot.textContent = total.toFixed(2);
            if (elIvaLabel) {
                elIvaLabel.textContent = esEdicion
                    ? 'IVA (16% materiales): $'
                    : 'IVA (16%): $';
            }
            calcularSaldoPendiente();
        }
```

### 2) `app/Services/RegistrarOrdenService.php`

Busca (~línea 907):

```php
        $ivaTotal = ($subtotalTrabajos + $subtotalMateriales) * 0.16;
        $totalPagar = $subtotalTrabajos + $subtotalMateriales + $ivaTotal;
```

**Reemplaza por:**

```php
        $ivaBase = $idOrdenEditar > 0
            ? $subtotalMateriales
            : ($subtotalTrabajos + $subtotalMateriales);
        $ivaTotal = round($ivaBase * 0.16, 2);
        $totalPagar = round($subtotalTrabajos + $subtotalMateriales + $ivaTotal, 2);
```

### 3) `resources/views/orders/orden_form.blade.php` (opcional, etiqueta IVA)

Busca:

```html
<strong>IVA (16%): $<span id="iva">0.00</span></strong><br>
```

**Reemplaza por:**

```html
<strong><span id="labelIva">IVA (16%): $</span><span id="iva">0.00</span></strong><br>
```

---

## Qué hace cada parte (ya implementado salvo IVA)

| Función | Dónde |
|---------|--------|
| Observaciones obligatorias + palabras accesorio | `orden_servicio.js` + `OrdenObservacionesValidator.php` |
| Comentarios obligatorios al editar | `orden_servicio.js` + `OrdenComentariosValidator.php` |
| Estatus «Recepción» bien escrito | `OrderStatus.php`, `OrdenListService.php`, JS listados |
| Tipo servicio en PDF | `TipoServicioCatalog.php`, `OrderPdfController.php` |
| Historial: solo «Abrir pestaña» | `historial_laravel.js`, `historial_page.blade.php` |
| **IVA solo materiales al editar** | Parches 1 y 2 arriba |

## Prueba rápida IVA (edición)

- Trabajos $1000 + Materiales $500 → IVA **$80**, Total **$1580** (no $240 de IVA).

## Para que el asistente lo aplique solo

En Cursor: cambia a **modo Agent** y escribe: `aplica los parches de SUBIR_COMPLETO_PLAN_ORDENES.md`
