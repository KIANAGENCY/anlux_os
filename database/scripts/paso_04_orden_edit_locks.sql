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
