export default [
	{
		files: ['assets/js/src/**/*.js'],
		languageOptions: {
			ecmaVersion: 2022,
			sourceType: 'module',
			globals: {
				window: 'readonly',
				document: 'readonly',
				jQuery: 'readonly',
				$: 'readonly',
				wp: 'readonly',
				console: 'readonly',
				promImporterAjaxObj: 'readonly',
				fetch: 'readonly',
				alert: 'readonly'
			}
		},
		rules: {
			'indent': ['error', 4],
			'quotes': ['error', 'single'],
			'semi': ['error', 'always'],
			'no-unused-vars': 'warn',
			'no-console': 'off',
			'prefer-const': 'error',
			'arrow-spacing': 'error',
			'no-var': 'error'
		}
	}
];
