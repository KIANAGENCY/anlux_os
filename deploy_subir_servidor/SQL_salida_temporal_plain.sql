-- Si ADD COLUMN IF NOT EXISTS falla (MySQL viejo), ejecuta una por una e ignora "Duplicate column":

ALTER TABLE orden_servicio_c ADD COLUMN salida_temporal_activa TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE orden_servicio_c ADD COLUMN fecha_salida_temporal DATETIME NULL;
ALTER TABLE orden_servicio_c ADD COLUMN fecha_regreso_temporal DATETIME NULL;
ALTER TABLE orden_servicio_c ADD COLUMN motivo_salida_temporal TEXT NULL;
ALTER TABLE orden_servicio_c ADD COLUMN firma_c_salida_temp VARCHAR(255) NULL;
ALTER TABLE orden_servicio_c ADD COLUMN firma_t_salida_temp VARCHAR(255) NULL;
