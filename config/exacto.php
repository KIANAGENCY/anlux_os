<?php

return [
    'telefono_secret' => env('EXACTO_TELEFONO_SECRET', ''),
    /*
    | Ruta del proyecto PHP legacy (exacto) para derivar el mismo pepper HMAC que
    | telefono_vault.php cuando no hay EXACTO_TELEFONO_SECRET. Debe apuntar al
    | directorio que legacy usa como dirname(__DIR__) en includes/telefono_vault.php.
    */
    'pepper_root' => env('EXACTO_PEPPER_ROOT', dirname(base_path()).DIRECTORY_SEPARATOR.'exacto'),

    'auth_shared_orders' => filter_var(env('EXACTO_AUTH_SHARED_ORDERS', false), FILTER_VALIDATE_BOOL),

    'login_allow_plaintext' => filter_var(env('EXACTO_LOGIN_ALLOW_PLAINTEXT_PASSWORD', false), FILTER_VALIDATE_BOOL),

    'remember_cookie' => env('EXACTO_REMEMBER_COOKIE', 'recuerdame'),

    'remember_days' => (int) env('EXACTO_REMEMBER_DAYS', 30),

    /*
    | Prefijo URL público (p. ej. /exacto_laravel/public) — se usa para firmas guardadas.
    */
    'public_url_prefix' => env('EXACTO_PUBLIC_PATH_PREFIX', ''),

    'firewall_disable' => filter_var(env('EXACTO_FW_DISABLE', false), FILTER_VALIDATE_BOOL),

    'firewall_skip_headers' => filter_var(env('EXACTO_FW_SKIP_HEADERS', false), FILTER_VALIDATE_BOOL),

    'csp' => env('EXACTO_CSP'),

    'csp_disable' => filter_var(env('EXACTO_CSP_DISABLE', false), FILTER_VALIDATE_BOOL),

    'hsts_max_age' => env('EXACTO_HSTS_MAX_AGE', 31536000),

    'legacy_url' => env('EXACTO_LEGACY_APP_URL', 'http://127.0.0.1/exacto'),

    /*
    | Texto opcional durante piloto/cutover (HTML escapado; saltos de línea respetados).
    */
    'cutover_banner' => trim((string) env('EXACTO_CUTOVER_BANNER', '')),

    /*
    | Enlaces discretos a URLs legacy (Órdenes, admin, login PHP) para rollback humano.
    */
    'show_legacy_escape_hatch' => filter_var(env('EXACTO_SHOW_LEGACY_ESCAPE_HATCH', false), FILTER_VALIDATE_BOOL),

    /** Minutos sin actividad para considerar un técnico "en línea" (impersonación). */
    'presence_online_minutes' => max(1, (int) env('EXACTO_PRESENCE_ONLINE_MINUTES', 10)),

    /*
    | Sesión única por cuenta. En false (default) se permite la misma cuenta abierta
    | en varios dispositivos a la vez; no se expulsa ni se bloquea el segundo acceso.
    */
    'session_single_device' => filter_var(env('EXACTO_SESSION_SINGLE_DEVICE', false), FILTER_VALIDATE_BOOL),

    /** Minutos sin actividad antes de cerrar sesión y exigir login de nuevo (8 horas = 480). */
    'session_idle_minutes' => max(1, (int) env('EXACTO_SESSION_IDLE_MINUTES', 480)),

    /*
    | Notificaciones WhatsApp al guardar/cambiar estatus.
    | Vacío o ausente en .env → sigue WHATSAPP_CLOUD_ENABLED.
    | false explícito = pausado (solo correo).
    */
    'whatsapp_notifications_enabled' => (($explicitWa = env('EXACTO_WHATSAPP_NOTIFICATIONS')) === null || $explicitWa === '')
        ? filter_var(env('WHATSAPP_CLOUD_ENABLED', false), FILTER_VALIDATE_BOOL)
        : filter_var($explicitWa, FILTER_VALIDATE_BOOL),

    /*
    | Solo APP_ENV=local + EXACTO_CATALOGO_SERSOP_GUEST=true: rutas del catálogo SERSOP
    | sin login ni perfil admin (útil en Laragon). No usar en producción.
    | (No usar app()->environment() aquí: durante la carga de config el offset «env»
    |  del contenedor aún no es el nombre del entorno.)
    */
    'catalogo_sersop_guest' => env('APP_ENV', 'production') === 'local'
        && filter_var(env('EXACTO_CATALOGO_SERSOP_GUEST', false), FILTER_VALIDATE_BOOL),
];
