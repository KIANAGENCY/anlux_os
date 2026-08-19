SET @db = DATABASE();

SET @sql = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'login' AND COLUMN_NAME = 'activo') > 0,
  'SELECT ''OK: columna activo ya existe'' AS resultado',
  'ALTER TABLE `login` ADD COLUMN `activo` TINYINT(1) NOT NULL DEFAULT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'login' AND COLUMN_NAME = 'last_seen_at') > 0,
  'SELECT ''OK: columna last_seen_at ya existe'' AS resultado',
  'ALTER TABLE `login` ADD COLUMN `last_seen_at` DATETIME NULL'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `orden_servicio_edit_locks` (
  `id_orden_c` INT NOT NULL,
  `locked_by_user_id` INT NOT NULL,
  `locked_by_nombre` VARCHAR(255) NOT NULL,
  `locked_at` DATETIME NOT NULL,
  `expires_at` DATETIME NOT NULL,
  PRIMARY KEY (`id_orden_c`),
  KEY `idx_orden_edit_lock_expires` (`expires_at`),
  KEY `idx_orden_edit_lock_user` (`locked_by_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SELECT 'Listo: revisa que existan las 2 columnas en login y las 2 tablas nuevas' AS resultado;
