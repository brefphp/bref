module.exports = {
    theme: {
        // screens: {
        //     'sm': '576px',
        //     'md': '768px',
        //     'lg': '992px',
        //     'xl': '1075px',
        // },
        fontFamily: {
            'sans': ['Open Sans', 'system-ui', 'BlinkMacSystemFont', '-apple-system', 'Helvetica Neue', 'sans-serif'],
            'title': ['Poppins', 'system-ui', 'BlinkMacSystemFont', '-apple-system', 'Helvetica Neue', 'sans-serif'],
            'bref': ['Dosis', 'Helvetica Neue', 'sans-serif'],
            'mono': ['Menlo', 'Monaco', 'Consolas', 'Liberation Mono', 'Courier New', 'monospace'],
        },
        fontWeight: {
            // 'hairline': 100,
            'thin': 200,
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
                // transparent: 'transparent',
                // black: '#22292f',
                // white: '#fff',
                // gray: {
                //     100: '#f7fafc',
                //     200: '#f7fafc',
                //     300: '#f7fafc',
                //     400: '#f7fafc',
                //     500: '#f7fafc',
                //     // ...
                //     900: '#1a202c',
                // },
                primary: {
                    900: '#1A202C',
                    800: '#3D4852', // #2D3748
                    700: '#4A5568',
                    600: '#718096',
                    500: '#A0AEC0', // #A0AEC0
                    400: '#B8C2CC', // #CBD5E0
                    300: '#E5E5E5', // #E2E8F0
                    200: '#EDF2F7',
                    100: '#F7FAFC',
                },
            },
            fontSize: {
                '7xl': '5rem',
                '8xl': '6rem',
                '9xl': '8rem',
            },
            spacing: {
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
