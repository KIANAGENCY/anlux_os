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
