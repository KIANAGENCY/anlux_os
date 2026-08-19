-- Sesión única por cuenta (login). Ejecutar en phpMyAdmin si no usas artisan migrate.
-- SHOW COLUMNS FROM login;

ALTER TABLE login ADD COLUMN active_session_id VARCHAR(128) NULL DEFAULT NULL;
ALTER TABLE login ADD COLUMN active_session_at DATETIME NULL DEFAULT NULL;
