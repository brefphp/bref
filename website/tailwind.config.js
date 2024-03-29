const defaultTheme = require('tailwindcss/defaultTheme');

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './*.{js,ts,jsx,tsx,md,mdx}',
        './src/**/*.{js,ts,jsx,tsx,md,mdx}',
    ],
    darkMode: 'class',
    theme: {
        fontFamily: {
            sans: ['var(--font-inter)', ...defaultTheme.fontFamily.sans],
            mono: "ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,Liberation Mono,Courier New,monospace",
        },
        extend: {
            colors: {
                blue: {
                    900: '#25516A',
                    800: '#266488',
                    700: '#2381B8',
                    600: '#258ECB',
                    500: '#3AA9E9',
                    400: '#5EBCF3',
                    300: '#8CD0F8',
                    200: '#BBE4FB',
                    100: '#EBF8FF',
                },
            },
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
        // ...
    ],
};
