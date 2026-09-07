-- Tabla anlux_login_sequences (NO se llama sequence_login)

CREATE TABLE IF NOT EXISTS `anlux_login_sequences` (
  `sequence_key` VARCHAR(80) NOT NULL,
  `next_id` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`sequence_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DELETE FROM `anlux_login_sequences` WHERE `sequence_key` = 'login_id_tecnico';

INSERT INTO `anlux_login_sequences` (`sequence_key`, `next_id`, `created_at`, `updated_at`)
SELECT 'login_id_tecnico', IFNULL(MAX(`id_tecnico`), 0) + 1, NOW(), NOW()
FROM `login`;

SELECT * FROM `anlux_login_sequences`;
