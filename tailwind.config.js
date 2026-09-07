import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

const runtimeScale = (name) => Object.fromEntries(
    [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950]
        .map((shade) => [shade, `rgb(var(--anlux-${name}-${shade}) / <alpha-value>)`]),
);

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.{js,ts,jsx,tsx}',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['var(--anlux-font-family)', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                blue: runtimeScale('blue'),
                cyan: runtimeScale('cyan'),
                slate: runtimeScale('slate'),
            },
        },
    },

    plugins: [forms],
};
