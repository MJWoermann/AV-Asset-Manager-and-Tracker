import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            colors: {
                brand: {
                    DEFAULT: '#00adb7',
                    turquoise: '#00adb7',
                    black: '#000000',
                    white: '#ffffff',
                    charcoal: '#545759',
                    silver: '#f0f0f0',
                    teal: '#005556',
                    plum: '#910e6b',
                    'grey-blue': '#9db6c5',
                    gold: '#dcaa0b',
                },
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
