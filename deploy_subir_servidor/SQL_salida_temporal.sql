-- Exacto: columnas salida temporal (si no puedes correr artisan migrate)
-- Base: joses16_exacto (ajusta el nombre si aplica)

ALTER TABLE orden_servicio_c
  ADD COLUMN IF NOT EXISTS salida_temporal_activa TINYINT(1) NOT NULL DEFAULT 0 AFTER estatus,
  ADD COLUMN IF NOT EXISTS fecha_salida_temporal DATETIME NULL AFTER salida_temporal_activa,
  ADD COLUMN IF NOT EXISTS fecha_regreso_temporal DATETIME NULL AFTER fecha_salida_temporal,
  ADD COLUMN IF NOT EXISTS motivo_salida_temporal TEXT NULL AFTER fecha_regreso_temporal,
  ADD COLUMN IF NOT EXISTS firma_c_salida_temp VARCHAR(255) NULL AFTER motivo_salida_temporal,
  ADD COLUMN IF NOT EXISTS firma_t_salida_temp VARCHAR(255) NULL AFTER firma_c_salida_temp;
