-- Solo tabla `login`. Credenciales: admin@soporte.exactolp.mx / ExactoAdmin12#
-- Requiere el mismo EXACTO_TELEFONO_SECRET que al generar estos valores cifrados.

INSERT INTO `login` (
  `id_tecnico`,
  `nombre_tecnico`,
  `nombre_tecnico_token`,
  `correo`,
  `contrasena`,
  `celular`,
  `perfil`
) VALUES (
  1,
  'v1:2UxQTlrz+yf06TjgYMKFptKM6SELRMLwX7OOsNcEgqxOM/xH7iVfGcM=',
  'd3c5a0c27207b17c2a10c24ff907d6d5bf8bb690f847298958c96417f890c732',
  'admin@soporte.exactolp.mx',
  '$2y$12$P9m13vmJVZ8HsY5KHnUWneNW1XbjyOsV5VILqyE03VkDWgkSLtoAq',
  'b2fc5567fafa882d5e172b9efd791ba8847eca01dc351a13b5fc01ee3ad1e1dd',
  'administrador'
);
