module.exports = {
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
                // primary: {
                //     900: '#1A202C',
                //     800: '#3D4852', // #2D3748
                //     700: '#4A5568',
                //     600: '#718096',
                //     500: '#A0AEC0', // #A0AEC0
                //     400: '#B8C2CC', // #CBD5E0
                //     300: '#E5E5E5', // #E2E8F0
                //     200: '#EDF2F7',
                //     100: '#F7FAFC',
                // },
            },
            fontSize: {
                '7xl': '5rem',
                '8xl': '6rem',
                '9xl': '8rem',
                '40px': '40px',
                '22px': '22px',
            },
            spacing: {
                '80': '20rem',
                '96': '24rem',
            },
            maxWidth: {
                '700px': '700px',
            },
        }
    },
    variants: {},
    plugins: [
        require('tailwind-css-variables')(
            {
                // modules
            },
            {
                // options
            }
        )
    ]
};
