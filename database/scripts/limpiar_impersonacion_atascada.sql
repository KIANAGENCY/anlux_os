-- Libera cuentas marcadas "en uso" por solicitudes viejas (ejecutar una vez si quedaron atascadas).
-- Después de desplegar el fix en ImpersonationService.php ya no debería volver a pasar.

UPDATE impersonation_requests
SET status = 'expired', resolved_at = NOW()
WHERE status IN ('pending', 'approved')
  AND expires_at > NOW();

-- Opcional: limpiar sesiones huérfanas sin actividad reciente (ajusta 10 al valor de EXACTO_PRESENCE_ONLINE_MINUTES).
UPDATE login
SET active_session_id = NULL, active_session_at = NULL
WHERE active_session_id IS NOT NULL
  AND TRIM(active_session_id) <> ''
  AND (last_seen_at IS NULL OR last_seen_at < DATE_SUB(NOW(), INTERVAL 10 MINUTE));
