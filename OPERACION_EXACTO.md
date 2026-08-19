# Operacion Exacto

## Requisitos minimos

- PHP 8.3
- Base de datos con migraciones al dia
- Worker de colas para `QUEUE_CONNECTION=database`
- Scheduler de Laravel ejecutandose cada minuto

## Arranque inicial

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --force
```

## Worker de colas

Para que los correos de cambio de estatus salgan en segundo plano, deja un worker activo:

```bash
php artisan queue:work database --queue=default --tries=3 --timeout=120
```

En desarrollo tambien puedes usar:

```bash
composer run dev
```

## Scheduler

Configura el scheduler del sistema para ejecutar:

```bash
php artisan schedule:run
```

Cada minuto. Las tareas programadas actuales hacen:

- limpieza diaria de `failed_jobs`
- limpieza diaria de batches de cola
- reinicio diario de workers para recargar codigo nuevo

## Despliegue en soporte.exactolp.mx (cPanel)

Subdominio **`soporte.exactolp.mx`** → proyecto **exacto_laravel** (no portal-proyectos).

### 1. Crear subdominio

cPanel → **Subdominios** → `soporte` + `exactolp.mx` → carpeta ej. `/home/joses16/soporte.exactolp.mx`.

### 2. Document Root (importante)

En **Dominios** o al editar el subdominio, la raíz debe ser la carpeta **`public`** de Laravel:

```text
/home/joses16/soporte.exactolp.mx/public
```

Si la raíz apunta solo a `soporte.exactolp.mx/` (sin `public`), el `.htaccess` de la raíz del proyecto redirige a `public/`.

### 3. Subir archivos

Sube **todo** exacto_laravel (app, bootstrap, config, public, vendor, …). En el servidor:

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
php artisan migrate --force
```

Permisos: `storage/` y `bootstrap/cache/` escribibles (775).

### 4. `.env` en producción

```env
APP_URL=https://soporte.exactolp.mx
APP_ENV=production
APP_DEBUG=false

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=joses16_exacto
DB_USERNAME=joses16_...
DB_PASSWORD=...

EXACTO_LEGACY_APP_URL=https://exactolp.mx
```

PHP **8.3+** en cPanel para este subdominio.

### 5. Probar

- `https://soporte.exactolp.mx/up` → debe responder OK (health de Laravel)
- `https://soporte.exactolp.mx/login` (o ruta de login configurada)

Dominio principal **exactolp.mx** puede seguir con otro sitio o legacy; Laravel vive en **soporte**.

---

## Despliegue recomendado

```bash
php artisan down
php artisan migrate --force
php artisan config:clear
php artisan cache:clear
php artisan queue:restart
php artisan up
```

## Verificacion rapida

- `php artisan migrate:status`
- `php artisan test`
- `php artisan queue:work --once`
- revisar `storage/logs/exacto-ops-*.log`

## Rollback basico

Si una liberacion falla:

1. `php artisan down`
2. revertir codigo a la version anterior
3. `php artisan migrate:rollback --step=1` solo si la ultima migracion fue parte del cambio fallido y no requiere conservar datos nuevos
4. `php artisan queue:restart`
5. `php artisan up`

## Notas operativas

- Las tablas auxiliares de ordenes y seguridad ya no se crean durante requests web; las migraciones son obligatorias.
- El alta de usuarios reserva `id_tecnico` mediante secuencia para evitar colisiones por concurrencia.
- Los errores de correo/PDF quedan registrados en `exacto-ops`.
