'use strict';

const prettier = require('eslint-config-prettier');
const importPlugin = require('eslint-plugin-import');
const tseslint = require('typescript-eslint');

module.exports = tseslint.config(
    {
        ignores: ['src/**', 'tests/**', 'website/**', 'docs/**', 'vendor/**', 'node_modules/**'],
    },
    {
        files: ['**/*.js'],
        languageOptions: {
            ecmaVersion: 2019,
            sourceType: 'commonjs',
        },
    },
    // Rules specific for TypeScript
    {
        files: ['**/*.ts'],
        extends: [
            importPlugin.flatConfigs.recommended,
            importPlugin.flatConfigs.typescript,
            ...tseslint.configs.recommended,
        ],
        languageOptions: {
            parserOptions: {
                project: 'tsconfig.json',
            },
        },
        rules: {
            // Because I got bit by "forgetting await" too many times
            '@typescript-eslint/no-floating-promises': 'error',
            '@typescript-eslint/no-misused-promises': 'error',
        },
    },
    // https://github.com/prettier/eslint-config-prettier
    prettier,
    {
        rules: {
            // This pattern is used by the CDK
            'no-new': ['off'],
        },
    },
);
