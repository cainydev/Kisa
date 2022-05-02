const defaultTheme = require('tailwindcss/defaultTheme');

module.exports = {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './vendor/wireui/wireui/resources/**/*.blade.php',
        './vendor/wireui/wireui/ts/**/*.ts',
        './vendor/wireui/wireui/src/View/**/*.php'
    ],
    presets: [
        require('./vendor/wireui/wireui/tailwind.config.js')
    ],
    theme: {
        truncate: {
            lines: {
                2: '2',
                3: '3',
                5: '5',
                8: '8',
            }
        },
        extend: {
            fontFamily: {
                sans: ['Nunito', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                'ksand': '#b8c672',
                'kgreen': '#6b8e6d',
                'kturq': '#7ec399'
            },
            transitionProperty: {
                'height': 'height',
                'width': 'width',
                'size': 'height, width, transform',
                'spacing': 'margin, padding',
                'flex': 'flex',
            }
        },
    },

    safelist: [{
            pattern: /max-w-(7xl|8xl|9xl|10xl)/,
        },
        {
            pattern: /text-(left|center|right)/,
        },
        {
            pattern: /grid-cols-(1|2|3|4|5|6|7|8|9|10|12)/,
        },
        {
            pattern: /gap-(1|2|3|4|5|6|7|8|9|10)/,
        },
        {
            pattern: /justify-(start|around|center|between|end)/,
        },
        {
            pattern: /fill-(orange|blue)-400/,
        },
        {
            pattern: /bg-(red|green|blue)-(300|400|500|600|700|800)/,
        },
        {
            pattern: /'text-white'/,
            variants: ['hover'],
        }
    ],

    plugins: [
        require('@tailwindcss/forms'),
        require('tailwindcss-truncate-multiline')(),
    ],
};