-- Prueba manual: la tabla impersonation_requests acepta INSERT
-- Ejecutar en anlux_os. Ajusta admin_id y target_id a tus id_tecnico reales.

INSERT INTO `impersonation_requests` (
  `admin_id`,
  `target_id`,
  `status`,
  `token`,
  `resolved_by_id`,
  `created_at`,
  `expires_at`,
  `resolved_at`
) VALUES (
  1,
  2,
  'pending',
  'prueba1234567890123456789012345678901234567890',
  NULL,
  NOW(),
  DATE_ADD(NOW(), INTERVAL 5 MINUTE),
  NULL
);

SELECT * FROM `impersonation_requests` ORDER BY `id` DESC LIMIT 5;
