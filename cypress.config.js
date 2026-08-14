module.exports =
	require('./packages/global-packages/packages/dev-tools/js/cypress/config')({
		rootDir: __dirname,
		projectId: 'blockera-site-toolkit',
		e2eSpecPattern: ['packages/**/*.toolkit.e2e.cy.js'],
		e2eExcludeSpecPattern: [],
		alwaysExcludeSpecPattern: ['packages/**/*.build.e2e.js'],
	});
