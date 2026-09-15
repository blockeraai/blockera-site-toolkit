/**
 * Site-toolkit Jest: only the site-toolkit package under global-packages.
 * Shared package unit tests run in blockera-global-packages / other product consumers.
 */
const path = require( 'path' );

const base = require( './packages/global-packages/packages/dev-jest/js/jest.config.js' );

module.exports = {
	...base,
	roots: [
		path.join(
			__dirname,
			'packages/global-packages/packages/site-toolkit'
		),
	],
	collectCoverageFrom: [
		'<rootDir>/packages/global-packages/packages/site-toolkit/**/*.js',
	],
};
