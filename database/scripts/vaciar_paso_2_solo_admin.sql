-- PASO 2 de 2 — Dejar solo admin@soporte.anlux.mx (misma contraseña)
-- Ejecuta DESPUÉS del paso 1. Pega TODO de una vez.

SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM `login` WHERE `correo` <> 'admin@soporte.anlux.mx';

UPDATE `login`
SET `activo` = 1, `perfil` = 'administrador'
WHERE `correo` = 'admin@soporte.anlux.mx';

INSERT INTO `orden_folio_sequence` (`anio`, `next_num`)
VALUES (2026, 1)
ON DUPLICATE KEY UPDATE `next_num` = 1;

SET FOREIGN_KEY_CHECKS = 1;

SELECT `id_tecnico`, `correo`, `perfil`, `activo` FROM `login`;
SELECT COUNT(*) AS ordenes_restantes FROM `orden_servicio_c`;
SELECT COUNT(*) AS chats_wa_restantes FROM `wa_conversations`;
SELECT COUNT(*) AS mensajes_wa_restantes FROM `wa_messages`;
