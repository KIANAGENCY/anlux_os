-- =============================================================================
-- RECREAR tabla login (compatible cPanel / MariaDB antiguo)
-- Base: joses16_exacto
--
-- Si phpMyAdmin marca error al pegar TODO junto, usa:
--   recrear_tabla_login_paso_a_paso.sql  (un bloque por ejecución)
-- =============================================================================

-- ----- BLOQUE A: borrar tablas viejas -----
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `login_remember_tokens`;
DROP TABLE IF EXISTS `login`;
SET FOREIGN_KEY_CHECKS = 1;

-- ----- BLOQUE B: crear login -----
-- utf8 (no utf8mb4) evita error #1071 "key was too long" en hosting viejo
CREATE TABLE `login` (
  `id_tecnico` INT NOT NULL AUTO_INCREMENT,
  `nombre_tecnico` VARCHAR(255) NOT NULL,
  `nombre_tecnico_token` VARCHAR(64) DEFAULT NULL,
  `correo` VARCHAR(255) NOT NULL,
  `contrasena` VARCHAR(255) NOT NULL,
  `celular` VARCHAR(64) DEFAULT NULL,
  `perfil` VARCHAR(50) NOT NULL DEFAULT 'tecnico',
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `last_seen_at` DATETIME DEFAULT NULL,
  `remember_token` VARCHAR(100) DEFAULT NULL,
  `email_verified_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id_tecnico`),
  UNIQUE KEY `login_correo_unique` (`correo`),
  KEY `idx_login_nombre_token` (`nombre_tecnico_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ----- BLOQUE C: tokens recordarme -----
CREATE TABLE `login_remember_tokens` (
  `selector` VARCHAR(32) NOT NULL,
  `token_hash` VARCHAR(64) NOT NULL,
  `id_tecnico` INT NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`selector`),
  KEY `idx_remember_user` (`id_tecnico`),
  KEY `idx_remember_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ----- BLOQUE D: admin -----
INSERT INTO `login` (
  `id_tecnico`,
  `nombre_tecnico`,
  `nombre_tecnico_token`,
  `correo`,
  `contrasena`,
  `celular`,
  `perfil`,
  `activo`
) VALUES (
  1,
  'v1:2UxQTlrz+yf06TjgYMKFptKM6SELRMLwX7OOsNcEgqxOM/xH7iVfGcM=',
  'd3c5a0c27207b17c2a10c24ff907d6d5bf8bb690f847298958c96417f890c732',
  'admin@soporte.exactolp.mx',
  '$2y$12$P9m13vmJVZ8HsY5KHnUWneNW1XbjyOsV5VILqyE03VkDWgkSLtoAq',
  'b2fc5567fafa882d5e172b9efd791ba8847eca01dc351a13b5fc01ee3ad1e1dd',
  'administrador',
  1
);

ALTER TABLE `login` AUTO_INCREMENT = 2;

-- ----- BLOQUE E: secuencia de IDs -----
CREATE TABLE IF NOT EXISTS `exacto_login_sequences` (
  `sequence_key` VARCHAR(80) NOT NULL,
  `next_id` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`sequence_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT INTO `exacto_login_sequences` (`sequence_key`, `next_id`, `created_at`, `updated_at`)
VALUES ('login_id_tecnico', 2, NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `next_id` = VALUES(`next_id`),
  `updated_at` = NOW();

-- ----- BLOQUE F: verificar -----
SHOW COLUMNS FROM `login`;
SELECT * FROM `login`;
SELECT * FROM `exacto_login_sequences`;
