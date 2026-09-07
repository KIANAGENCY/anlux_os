# Theme

## Compact token summary

- Product palette: blue application chrome (`#2563eb`, `#1d4ed8`, `#1e3a8a`), white surfaces, slate text (`#0f172a`, `#334155`, `#64748b`), pale blue support surfaces (`#eff6ff`).
- Success/action: green (`#16a34a`, hover `#15803d`).
- Warning: amber (`#f59e0b`) and pale amber.
- Error/destructive secondary: red (`#dc2626`) with pale red surfaces.
- Font: Tailwind system sans stack / Figtree where available; dense operational UI with 12-18px text.
- Spacing: Tailwind 4px scale. Modal padding generally 16-20px and gaps 8-16px.
- Radius: 8px controls, 16px modal containers, pills for compact status elements.
- Shadows: `shadow-lg`/`shadow-2xl` on elevated dialogs; borders supply most grouping.
- Breakpoints: Tailwind defaults (`sm 640`, `md 768`, `lg 1024`).
- Icons: Font Awesome 6.4.
- UX: high-contrast, touch-friendly operational forms, Spanish copy, explicit confirmation and validation.

## Raw source: `tailwind.config.js`

```js
import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],
    theme: {
        extend: {
            fontFamily: { sans: ['Figtree', ...defaultTheme.fontFamily.sans] },
        },
    },
    plugins: [forms],
};
```

## Raw source: `resources/css/app.css`

```css
@tailwind base;
@tailwind components;
@tailwind utilities;
```

