import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                'primary': '#C8102E',
                'primary-dark': '#9F0D24',
                'text': '#475569',
                'texthighlight': '#8A1022',
                'background': '#FFF7F8',
            }
        },
    },

    plugins: [forms],
};
