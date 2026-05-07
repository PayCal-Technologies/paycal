import globals from 'globals';

export default [
  {
    files: ['html/js/**/*.js', 'tools/**/*.mjs'],
    ignores: ['html/js/vendor/**'],
    languageOptions: {
      ecmaVersion: 'latest',
      sourceType: 'module',
      globals: {
        ...globals.browser,
        ...globals.node,
        PayCalCore: 'readonly',
      },
    },
    rules: {
      'no-unused-vars': ['error', { argsIgnorePattern: '^_', varsIgnorePattern: '^_', caughtErrorsIgnorePattern: '^_' }],
      'no-undef': 'error',
      'no-redeclare': 'error',
    },
  },
];
