import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                serif: ['Playfair Display', ...defaultTheme.fontFamily.serif], // Adding serif for that premium feel in the screenshot
            },
            colors: {
                primary: {
                    DEFAULT: '#059669', // emerald-600
                    50: '#ecfdf5',
                    100: '#d1fae5',
                    200: '#a7f3d0',
                    300: '#6ee7b7',
                    400: '#34d399',
                    500: '#10b981',
                    600: '#059669',
                    700: '#047857',
                    800: '#065f46',
                    900: '#064e3b',
                },
                gold: {
                    DEFAULT: '#B8860B', // DarkGoldenRod
                    light: '#DAA520', // GoldenRod
                    dark: '#8B6508',
                    100: '#F9F1D8',
                    200: '#F3E4B2',
                },
                cream: {
                    DEFAULT: '#FDFBF7',
                    50: '#FEFDF9',
                    100: '#FDFBF7',
                    200: '#FAF6E6', // Slightly darker cream
                    300: '#F5EDC5',
                }
            }
        },
    },

    plugins: [forms],
};
