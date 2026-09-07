# Extractable Components

## AnluxUiModal

- Source: `resources/views/partials/anlux-ui-modal.blade.php`
- Category: basic
- Description: Shared alert, prompt, and confirmation modal.
- Extractable props: `title`, `message`, `icon`, `showCancel`, `confirmText`, `cancelText`.
- Hardcoded: centered card layout, icon circle, input slot, blue primary action.

## HeaderFlujoTres

- Source: `resources/views/partials/header-flujo-tres.blade.php`
- Category: layout
- Description: Three-way navigation for orders, new service order, and PDF history.
- Extractable props: `activeItem`.
- Hardcoded: destinations, Font Awesome icons, blue active/inactive visual system.

## NavAnluxUserBar

- Source: `resources/views/partials/nav-anlux-user-bar.blade.php`
- Category: layout
- Description: Technician identity, impersonation state, and account switcher.
- Extractable props: `technicianName`, `isImpersonating`, `canSwitchAccount`.
- Hardcoded: account-state legend and Anlux application roles.

## DeliverySignatureModal

- Source: `resources/views/orders/orden_form.blade.php` lines 1808-1885
- Category: basic
- Description: Recipient selection, recipient identity, two signatures, equipment context, and final notification action.
- Extractable props: `recipientType`, `recipientName`, `equipmentBrand`, `equipmentModel`, `whatsappEnabled`.
- Hardcoded: signature canvases, validation labels, cancel and submit actions.

