# Exacto: cutover Laravel ↔ legacy

## Rutas principales (Laravel)

| Área | Ruta |
|------|------|
| Listado órdenes | `GET /ordenes` |
| API listado | `GET /api/ordenes` (query: `search`, `startDate`, `endDate`, `estatus`, `page`, `perPage`) |
| API estatus | `POST /api/ordenes/estatus` (`id`, `estatus` rojo/naranja/amarillo/verde, `_token`) |
| API registrar orden | `POST /api/ordenes/registrar` (`orders.registrar`) |
| Formulario orden | `GET /orden_servicio`, `GET /orden_servicio/{id}` |
| PDF | `GET /pdf/orden/{id}` (`?inline=1` para iframe) |
| Historial | `GET /historial` |

## Assets

- JS listado: `public/legacy/js/ordenes_laravel.js`
- JS historial: `public/legacy/js/historial_laravel.js`
- Logo esperado: `public/legacy/public/img/logo.jpeg` (copiar desde legacy si falta)

## Variables `.env` (ver `config/exacto.php`)

- `EXACTO_*` según entorno (firewall, CSP, cookie remember, etc.)
- `EXACTO_LOGIN_ALLOW_PLAINTEXT_PASSWORD` solo migración/debug
- `EXACTO_PEPPER_ROOT` / secretos de vault si aplica

## Cookie «recordar» legacy

- Nombre por defecto: `recuerdame` (`config('exacto.remember_cookie')`)
- Emisión al marcar «recordar» en login; revocación en logout

---

## Fase 5 — Cutover gradual y rollback

Objetivo: que los usuarios usen Laravel como front principal sin corte brusco, con **plan de vuelta atrás** claro y **avisos operativos** durante el piloto.

### Aviso visual y «escape hatch» (misma BD, sesiones distintas)

Laravel y el PHP legacy comparten tablas (`login`, órdenes, etc.), pero **la sesión HTTP no es la misma**. No se debe redirigir automáticamente el tráfico autenticado de Laravel al legacy esperando que siga logueado.

En su lugar:

1. **`EXACTO_CUTOVER_BANNER`** — Texto breve (una o varias líneas) mostrado arriba del área de trabajo y en pantallas de invitado (login). HTML no permitido (se escapa); sirve para avisos del tipo «Piloto Laravel — reportar incidencias a …».

2. **`EXACTO_SHOW_LEGACY_ESCAPE_HATCH=true`** — Muestra enlaces a páginas concretas del legacy (`ordenes.php`, `orden_servicio.php`, etc.) que abren en otra pestaña. El técnico o admin debe **iniciar sesión en el legacy** si entra ahí.

3. **`EXACTO_LEGACY_APP_URL`** — URL base del proyecto PHP anterior, **sin barra final** (ej. `http://localhost/exacto` o tu virtual host Laragon).

### Redirección opcional desde el legacy (piloto)

En el proyecto PHP `exacto`, tras cargar variables de entorno, `includes/firewall.php` puede enviar al usuario al front de Laravel si defines en el **`.env` del legacy**:

- `EXACTO_REDIRECT_LEGACY_TO_LARAVEL=1` (u `true`)
- `EXACTO_LARAVEL_PUBLIC_URL` — URL base del `public/` de Laravel, **sin barra final** (ej. `http://127.0.0.1/exacto_laravel/public`)

Solo aplica a páginas mapeadas (`login.php`, `ordenes.php`, `orden_servicio.php`, `historial_ordenes.php`, `admin_destino.php`, `registro.php`, `catalogo_sersop.php`, `seguridad_actividad.php`). **No** redirige `actions/*` ni `scripts/*` (APIs, PDF, mantenimiento).

### Conmutación de tráfico (recomendado)

El corte limpio se hace en **servidor web / DNS / document root**, no solo con variables de entorno:

- Apunta el host público (o el path que usan hoy) al **DocumentRoot** o al **front controller** de Laravel (`public/`).
- Mantén el legacy accesible en **otro host o subruta** (ya referenciado en `EXACTO_LEGACY_APP_URL`) para soporte y rollback.

### Checklist previo a go-live

- [ ] `.env` de producción: `APP_URL`, `DB_*`, `EXACTO_LEGACY_APP_URL`, secretos (`EXACTO_TELEFONO_SECRET` si aplica).
- [ ] Asset `public/legacy/public/img/logo.jpeg` presente (copia desde legacy si falta).
- [ ] Probar: login, listado `/ordenes`, cambio de estatus, alta/edición `/orden_servicio`, PDF `/pdf/orden/{id}`, historial, panel admin (solo rol admin), registro admin, catálogo SERSOP.
- [ ] Probar cookie «recordar» y cierre de sesión en ambos entornos de prueba.
- [ ] Definir ventana de piloto: activar `EXACTO_CUTOVER_BANNER` y `EXACTO_SHOW_LEGACY_ESCAPE_HATCH` solo si hace falta.

### Rollback rápido

1. Revertir el virtual host o el proxy para que el tráfico principal vuelva al **document root del legacy**.
2. Opcional: desactivar banner y escape hatch en Laravel (`EXACTO_CUTOVER_BANNER` vacío, `EXACTO_SHOW_LEGACY_ESCAPE_HATCH=false`).
3. Comunicar al equipo que la sesión vuelve a ser **solo la del PHP clásico**.

### Tabla de equivalencias Laravel ↔ legacy (referencia)

| Laravel | Legacy (mismo origen que `EXACTO_LEGACY_APP_URL`) |
|--------|-----------------------------------------------------|
| `GET /ordenes` | `ordenes.php` |
| `GET /orden_servicio`, `GET /orden_servicio/{id}` | `orden_servicio.php` (query `id`, `ref=ordenes` según flujo) |
| `GET /historial` | `historial_ordenes.php` |
| `GET /pdf/orden/{id}` | `actions/generar_orden_pdf.php?id={id}` |
| `GET /admin` | `admin_destino.php` |
| `GET /admin/registro` | `registro.php` |
| `GET /admin/catalogo-sersop` | `catalogo_sersop.php` |
| `GET /admin/seguridad` | `seguridad_actividad.php` |
| APIs bajo `/api/ordenes`, registro y estatus | `actions/ordenes_api.php`, `actions/registrar_orden.php` (sesión PHP legacy) |

### Salud

- Laravel expone `GET /up` (health check por defecto de Laravel 11) para balanceadores o monitoreo.