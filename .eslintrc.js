'use strict';

module.exports = {
    extends: [
        // https://github.com/prettier/eslint-config-prettier
        'prettier',
    ],
    parserOptions: {
        ecmaVersion: 2019,
    },
    root: true,
    ignorePatterns: ['src/**', 'tests/**', 'website/**', 'vendor/**'],
    plugins: ['import', '@typescript-eslint'],
    overrides: [
        // Rules specific for TypeScript
        {
            files: ['**/*.ts'],
            // We must use a different parser that supports TypeScript
            parser: '@typescript-eslint/parser',
            // Extra rules specific to TypeScript
            extends: [
                'plugin:import/recommended',
                'plugin:import/typescript',
                'plugin:@typescript-eslint/recommended',
            ],
            parserOptions: {
                project: 'tsconfig.json',
            },
            rules: {
                // Because I got bit by "forgetting await" too many times
                '@typescript-eslint/no-floating-promises': 'error',
                '@typescript-eslint/no-misused-promises': 'error',
            },
        },
    ],
    rules: {
        // This pattern is used by the CDK
        'no-new': ['off'],
    },
};
