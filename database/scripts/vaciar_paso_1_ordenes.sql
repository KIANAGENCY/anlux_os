-- PASO 1 de 2 — Órdenes, WhatsApp chat y datos relacionados
-- phpMyAdmin: selecciona la BD → pestaña SQL → pega TODO → Continuar
-- Si sale error, copia el mensaje (no ejecutes línea por línea).

SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM `materiales_orden`;
DELETE FROM `trabajos_orden`;
DELETE FROM `equipos_orden`;
DELETE FROM `orden_servicio_t`;
DELETE FROM `orden_servicio_tecnico_log`;
DELETE FROM `orden_servicio_audit_log`;
DELETE FROM `orden_servicio_nombre_busqueda`;
DELETE FROM `orden_servicio_c`;
DELETE FROM `orden_folio_sequence`;
DELETE FROM `order_whatsapp_notifications`;
DELETE FROM `wa_messages`;
DELETE FROM `wa_conversations`;
DELETE FROM `orden_servicio_edit_locks`;
DELETE FROM `impersonation_requests`;
DELETE FROM `failed_jobs`;
DELETE FROM `jobs`;
DELETE FROM `job_batches`;
DELETE FROM `security_activity_log`;

ALTER TABLE `materiales_orden` AUTO_INCREMENT = 1;
ALTER TABLE `trabajos_orden` AUTO_INCREMENT = 1;
ALTER TABLE `equipos_orden` AUTO_INCREMENT = 1;
ALTER TABLE `orden_servicio_t` AUTO_INCREMENT = 1;
ALTER TABLE `orden_servicio_c` AUTO_INCREMENT = 1;
ALTER TABLE `wa_messages` AUTO_INCREMENT = 1;
ALTER TABLE `wa_conversations` AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

SELECT COUNT(*) AS ordenes_restantes FROM `orden_servicio_c`;
SELECT COUNT(*) AS chats_wa_restantes FROM `wa_conversations`;
SELECT COUNT(*) AS mensajes_wa_restantes FROM `wa_messages`;
