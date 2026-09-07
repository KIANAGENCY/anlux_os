# Anlux OS — Sistema de diseño operativo

## Producto y usuarios

Anlux OS es una aplicación en español de México para gestionar órdenes de servicio técnico. La usan técnicos en escritorio/tablet y administradores en un panel operativo. La interfaz debe acelerar lectura, captura, confirmación y seguimiento; no debe parecer una landing page ni un dashboard genérico.

## Principios

1. Una tarea principal por sección y contexto de sesión siempre visible.
2. Densidad operativa legible: encabezados compactos, tablas escaneables y formularios agrupados.
3. Acciones destructivas, sincronización, mantenimiento, impersonación y envíos siempre se confirman.
4. Labels explícitos, errores junto al campo, estados con texto e icono además de color.
5. Desktop-first para 1366×768 y tablet landscape; mobile sigue siendo usable.
6. Mantener IDs, rutas, payloads, permisos y lógica actual. Blade solo monta roots y props JSON.

## Tokens

### Color

- `--anlux-primary: #2563eb`; hover `#1d4ed8`; deep `#1e3a8a`; pale `#eff6ff`.
- Fondo de aplicación `#f8fafc`; superficies `#ffffff`; superficie secundaria `#f1f5f9`.
- Texto fuerte `#0f172a`; normal `#334155`; tenue `#64748b`; borde `#cbd5e1`; borde suave `#e2e8f0`.
- Éxito/guardar/enviar `#16a34a`; advertencia/terceros `#f59e0b`; error/destructivo `#dc2626`.
- Prohibidos: gradients decorativos, morado, glow, dark mode predeterminado y colores fuera de esta paleta.

### Tipografía

- Figtree con fallback system sans.
- Display 30/36 semibold; H1 24/32 bold; H2 20/28 semibold; H3 16/24 semibold.
- Body 14/22; small 12/18; label 13/18 semibold. Tablas 13–14 px.
- Mayúsculas y tracking solo para eyebrows muy breves; nunca para párrafos o labels de formulario.

### Espaciado, forma y elevación

- Escala base 4 px: 4, 8, 12, 16, 20, 24, 32, 40.
- Controles: radio 8 px y altura mínima 44 px en acciones primarias.
- Cards/modales: radio 12–16 px. Badges de estado pueden ser pill; botones y navegación no.
- Sombra de card `0 1px 2px rgb(15 23 42 / .06), 0 8px 24px rgb(15 23 42 / .06)`.
- La agrupación depende primero de bordes visibles, después de sombras contenidas.
- Motion 150–200 ms para hover/focus; respetar `prefers-reduced-motion`.

### Focus y accesibilidad

- Focus ring azul de 3 px con offset blanco; contraste AA para texto principal.
- Iconos Font Awesome existentes con `aria-hidden`; botones icon-only requieren label accesible.
- No codificar éxito, presencia, locks o estado únicamente con color.

## Componentes

- **Button**: primary azul; success verde solo guardar/enviar; secondary blanco con borde; warning ámbar; danger rojo. Estados loading/disabled conservan label y ancho.
- **Input / Select / Textarea**: label visible, ayuda opcional, error debajo; borde slate y focus azul. Select de cuenta mínimo 240 px en admin.
- **Card**: superficie blanca, borde suave, padding 16–24 px. Sin cards anidadas innecesarias.
- **Badge / Status**: punto o icono + texto. Presencia: En línea, En uso, Sin conexión, Inactiva.
- **PageHeader**: título, contexto breve y acciones a la derecha; sin hero gradients.
- **Table**: header slate claro, filas de 44–52 px, hover tenue, acciones consistentes y overflow horizontal en tablet.
- **Modal**: overlay oscuro, encabezado explícito, contenido enfocado, footer estable. Focus trap, Escape y restore focus.
- **Empty/Error/Success state**: icono sobrio, título, explicación y una siguiente acción útil.
- **Loading overlay**: spinner azul, mensaje concreto y bloqueo solo cuando la operación lo exige.
- **LogoCard**: tarjeta blanca elevada con ring sutil y el archivo real `public/legacy/public/img/logo.jpeg`; nunca sustituir por texto, iniciales, emoji o SVG inventado.

## Layouts

### AuthLayout

Lienzo slate muy claro. Card centrada de 420–480 px con LogoCard, título, formulario y ayuda. Una sola columna, sin ilustración de marketing. Acciones primarias full-width en narrow.

### AppShell

LogoCard arriba y barra operativa estable debajo. Área izquierda para contexto/sección; derecha para cuenta, estado/técnico y sesión. Contenido máximo 1280 px con padding 16–24 px. En tablet la barra se divide en dos filas deliberadas, no por colapso accidental.

### AdminShell

LogoCard independiente arriba. Debajo, barra blanca con borde: navegación Panel, Catálogo, Registro, Usuarios, Seguridad y Folios en una fila desplazable controlada; bloque de sesión separado y alineado. El selector “Cambiar de cuenta” conserva ancho; la leyenda de estados es horizontal; Técnico, Órdenes y Cerrar sesión permanecen visibles. En tablet: fila 1 navegación, fila 2 contexto de cuenta/acciones.

## Pantallas críticas

- **Login**: logo real, título “Acceso a Anlux OS”, usuario/email y contraseña, recordar sesión, recuperar contraseña y CTA Entrar. Errores inline y mensaje de sesión arriba.
- **Órdenes**: PageHeader con nueva orden; filtros persistentes y compactos; tabla prioriza folio, cliente/equipo, técnico, estado y acciones. Estados legibles, empty state con CTA.
- **Formulario de orden**: secciones identificables, grid de dos columnas en landscape, resumen/totales estable y guardado claro. Locks y polling WhatsApp visibles sin dominar. Firmas de entrega en modal: dos paneles lado a lado, stack en narrow.
- **Nav admin**: composición estable descrita en AdminShell; nunca leyenda vertical, select colapsado o enlaces repartidos accidentalmente.
- **Panel de mantenimiento**: estado global prominente pero sobrio, explicación del impacto y control con confirmación; accesos administrativos debajo en grid compacto.

## Do / Don’t

- Do: claridad, bordes, jerarquía, labels, whitespace funcional, estados explícitos y acciones predecibles.
- Do: usar el logo real y la paleta azul Anlux en toda superficie.
- Don’t: gradients decorativos, glassmorphism, glow, serif, morado, crema/terracota, dark mode inicial, pills excesivos o métricas inventadas.
- Don’t: cambiar contratos, agregar funciones de negocio o ocultar impersonación/WhatsApp/locks.
