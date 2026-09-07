# Page Dependency Trees

## `/orden_servicio/{id}` — Order editor and delivery signatures

Entry: `resources/views/orders/orden_page.blade.php`

Dependencies:

- `resources/views/layouts/anlux_app.blade.php`
  - `public/legacy/assets/css/app.css`
  - Font Awesome 6.4 CDN
- `resources/views/orders/orden_form.blade.php`
  - `resources/views/partials/nav-app.blade.php`
    - `resources/views/partials/nav-anlux-user-bar.blade.php`
      - `resources/views/partials/anlux-ui-modal.blade.php`
      - `public/legacy/assets/js/anlux_ui.js`
      - `public/legacy/assets/js/nav_impersonacion.js`
  - `resources/views/partials/header-flujo-tres.blade.php`
  - `public/legacy/assets/js/orden_servicio.js`
  - `public/legacy/assets/js/orden_firmas_entrega.js`

Target component: `#modalFirmasEntrega`, around lines 1808-1885 of `orden_form.blade.php`.

## `/ordenes` — Orders list

Entry: `resources/views/orders/index.blade.php`

Dependencies:

- `resources/views/layouts/anlux_app.blade.php`
- `resources/views/partials/nav-app.blade.php`
- `resources/views/partials/header-flujo-tres.blade.php`
- `public/legacy/assets/css/ordenes.css`
- `public/legacy/js/ordenes_laravel.js`

## `/` — Home

Entry: `resources/views/index.blade.php`

Dependencies:

- `resources/views/layouts/anlux_app.blade.php`
- `resources/views/partials/nav-app.blade.php`
- `resources/views/partials/header-flujo-tres.blade.php`
- `public/legacy/assets/css/home.css`

