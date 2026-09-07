# Desplegar Anlux OS en Vercel

Anlux es Laravel (PHP). En Vercel corre con el runtime comunitario **vercel-php** (serverless). Funciona para demo/piloto; para producción seria sigue siendo preferible cPanel/VPS.

## Requisitos previos

1. Cuenta en [Vercel](https://vercel.com) + CLI: `npm i -g vercel`
2. Base de datos **MySQL/MariaDB externa** (Vercel no te da MySQL del panel)
3. `APP_KEY` generado: `php artisan key:generate --show`
4. Migraciones aplicadas **contra esa DB** (desde tu PC o CI):

```bash
# Con .env apuntando a la DB remota
php artisan migrate --force
php artisan anlux:create-admin   # o tu seeder de admin
```

## Archivos añadidos

| Archivo | Rol |
|---|---|
| `api/index.php` | Entrypoint PHP serverless |
| `vercel.json` | Runtime PHP 8.3, rutas estáticas + catch-all |
| `.vercelignore` | Excluye basura del bundle |
| `.env.vercel.example` | Plantilla de variables |

## Deploy

```bash
cd c:\laragon\www\anlux_os
npm run build
vercel login
vercel          # preview
vercel --prod   # producción
```

En el panel del proyecto:

1. **Settings → Environment Variables**: copia desde `.env.vercel.example` (sobre todo `APP_KEY`, `APP_URL`, `DB_*`).
2. **Settings → Domains**: agrega tu dominio y sigue el DNS que indique Vercel.
3. Tras el primer deploy, actualiza `APP_URL` a `https://tu-dominio.com` y redespliega.

## Qué sí / qué no

**Sí (con DB externa):** login, órdenes, admin, assets Vite, dominio custom.

**Limitado / frágil:**
- Archivos en disco (logos subidos, storage local) → se pierden entre instancias (`/tmp` efímero). Usa S3 más adelante si lo necesitas.
- Colas en background → forzadas a `sync` (el request espera).
- Cold starts lentos y límite de tamaño del bundle Laravel.
- WhatsApp/PDF pueden chocar con `maxDuration` (60s en el config).

## Fallos frecuentes

| Síntoma | Qué revisar |
|---|---|
| 500 al abrir | `APP_KEY`, logs en Vercel → Functions |
| No conecta DB | `DB_*`, firewall del host MySQL debe permitir IPs de Vercel (o `0.0.0.0/0` en demo) |
| CSS roto | Que el build haya generado `public/build` (`npm run build` en buildCommand) |
| Logout/sesión rara | `SESSION_DRIVER=database` + tablas de sesión migradas |

## Alternativa oficial (más robusta)

Vercel documenta Laravel con **Docker + FrankenPHP** (`Dockerfile.vercel`). Es más pesado de mantener; este repo usa `vercel-php` por simplicidad.
