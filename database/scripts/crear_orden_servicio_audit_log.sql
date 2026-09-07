-- Tabla requerida para auditoría; sin ella los TÉCNICOS no veían órdenes (filtro devolvía vacío).
-- Ejecutar en anlux_os si falta la tabla orden_servicio_audit_log

CREATE TABLE IF NOT EXISTS `orden_servicio_audit_log` (
  `id_audit` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_orden_c` INT UNSIGNED NOT NULL,
  `folio` VARCHAR(50) DEFAULT NULL,
  `accion` VARCHAR(50) NOT NULL,
  `usuario` VARCHAR(150) DEFAULT NULL,
  `detalles` TEXT DEFAULT NULL,
  `ip` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `fecha` DATETIME NOT NULL,
  PRIMARY KEY (`id_audit`),
  KEY `idx_orden_audit_orden_fecha` (`id_orden_c`, `fecha`),
  KEY `idx_orden_audit_accion_fecha` (`accion`, `fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
