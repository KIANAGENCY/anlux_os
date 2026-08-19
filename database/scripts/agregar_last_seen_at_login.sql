-- Ejecutar en phpMyAdmin (base joses16_exacto).
-- La columna `activo` ya existe en tu servidor; NO la vuelvas a crear.

-- 1) Ver qué columnas tiene login:
-- SHOW COLUMNS FROM login;

-- 2) Solo si NO aparece last_seen_at, ejecuta UNA línea:
ALTER TABLE login ADD COLUMN last_seen_at DATETIME NULL;

-- 3) Opcional: marcar técnicos activos (si activo existe)
-- UPDATE login SET activo = 1 WHERE activo IS NULL;
