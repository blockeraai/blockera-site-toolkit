// Edit packages/global-packages/packages/dev-tools/root-configs/jest.config.blockera-site-toolkit.js
// project:bootstrap copies this to the host repo root for --project=blockera-site-toolkit.
/**
 * Site-toolkit Jest: only the site-toolkit package under global-packages.
 * Shared package unit tests run in blockera-global-packages / other product consumers.
 */
const path = require('path');

const base = require('./packages/global-packages/packages/dev-jest/js/jest.config.js');

module.exports = {
	...base,
	roots: [
		path.join(__dirname, 'packages/global-packages/packages/site-toolkit'),
	],
	collectCoverageFrom: [
		'<rootDir>/packages/global-packages/packages/site-toolkit/**/*.js',
	],
};
