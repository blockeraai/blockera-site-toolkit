// Toolkit still uses @wordpress/scripts 31 (ESLint 8). Shared GP eslint/config.js
// is flat-only for scripts 35. Keep a classic eslintrc until this host upgrades.
const ignorePatterns = require('./packages/global-packages/packages/dev-tools/js/eslint/ignore');

module.exports = {
	root: true,
	ignorePatterns,
	extends: ['plugin:@wordpress/eslint-plugin/recommended'],
	rules: {
		'import/no-extraneous-dependencies': 'off',
		'@wordpress/i18n-text-domain': [
			'error',
			{
				allowedTextDomain: ['blockera', 'blockera-site-toolkit'],
			},
		],
	},
};
