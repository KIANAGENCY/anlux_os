-- =============================================================================
-- Anlux: impersonación + locks + usuarios activos
-- Ejecutar en phpMyAdmin → base anlux_os → pestaña SQL
--
-- IMPORTANTE (cPanel / MariaDB):
--   - NO uses "IF NOT EXISTS" en ALTER TABLE (no funciona en muchos hostings).
--   - Ejecuta UN BLOQUE a la vez (copia/pega cada sección y pulsa Continuar).
--   - Si dice "Duplicate column name" → esa columna ya existe: sigue al siguiente bloque.
-- =============================================================================

-- ----- BLOQUE 1: columna activo en login -----
ALTER TABLE `login`
    ADD COLUMN `activo` TINYINT(1) NOT NULL DEFAULT 1;

-- ----- BLOQUE 2: columna last_seen_at en login -----
ALTER TABLE `login`
    ADD COLUMN `last_seen_at` DATETIME NULL;

-- ----- BLOQUE 3: tabla solicitudes de impersonación -----
CREATE TABLE IF NOT EXISTS `impersonation_requests` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `admin_id` INT NOT NULL,
    `target_id` INT NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `token` VARCHAR(64) NOT NULL,
    `resolved_by_id` INT NULL,
    `created_at` DATETIME NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `resolved_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `impersonation_requests_token_unique` (`token`),
    KEY `idx_impersonation_status_expires` (`status`, `expires_at`),
    KEY `idx_impersonation_admin` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----- BLOQUE 4: tabla locks de edición de órdenes -----
CREATE TABLE IF NOT EXISTS `orden_servicio_edit_locks` (
    `id_orden_c` INT NOT NULL,
    `locked_by_user_id` INT NOT NULL,
    `locked_by_nombre` VARCHAR(255) NOT NULL,
    `locked_at` DATETIME NOT NULL,
    `expires_at` DATETIME NOT NULL,
    PRIMARY KEY (`id_orden_c`),
    KEY `idx_orden_edit_lock_expires` (`expires_at`),
    KEY `idx_orden_edit_lock_user` (`locked_by_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
