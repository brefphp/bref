const colors = require('tailwindcss/colors')

module.exports = {
    purge: [
        './template/**/*.twig',
        './template/**/*.css',
        './template/**/*.js',
        '../docs/**/*.md',
    ],
    theme: {
        fontFamily: {
            'sans': ['Open Sans', 'system-ui', 'BlinkMacSystemFont', '-apple-system', 'Helvetica Neue', 'sans-serif'],
            'title': ['Poppins', 'system-ui', 'BlinkMacSystemFont', '-apple-system', 'Helvetica Neue', 'sans-serif'],
            'bref': ['Dosis', 'Helvetica Neue', 'sans-serif'],
            'mono': ['Menlo', 'Monaco', 'Consolas', 'Liberation Mono', 'Courier New', 'monospace'],
        },
        fontWeight: {
            // 'hairline': 100,
            // 'thin': 200,
            'light': 300,
            'normal': 400,
            // 'medium': 500,
            'semibold': 600,
            'bold': 700,
            // 'extrabold': 800,
            // 'black': 900,
        },
        extend: {
            colors: {
                orange: colors.orange,
                gray: {
                    100: '#f7fafc',
                    200: '#edf2f7',
                    300: '#e2e8f0',
                    400: '#cbd5e0',
                    500: '#a0aec0',
                    600: '#718096',
                    700: '#4a5568',
                    800: '#2d3748',
                    900: '#1a202c',
                },
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
                'green': {
                    50: '#F2FCF9',
                    100: '#E6F8F4',
                    200: '#BFEEE3',
                    300: '#99E3D1',
                    400: '#4DCFAF',
                    500: '#00BA8D',
                    600: '#00A77F',
                    700: '#007055',
                    800: '#00543F',
                    900: '#00382A',
                },
                'red': {
                    50: '#FFF8F7',
                    100: '#FFF1F0',
                    200: '#FFDDD9',
                    300: '#FFC8C2',
                    400: '#FF9F94',
                    500: '#FF7666',
                    600: '#E66A5C',
                    700: '#99473D',
                    800: '#73352E',
                    900: '#4D231F',
                },
            },
            fontSize: {
                '40px': ['40px', '60px'],
            },
            maxWidth: {
                '700px': '700px',
            },
        }
    },
    variants: {},
};
