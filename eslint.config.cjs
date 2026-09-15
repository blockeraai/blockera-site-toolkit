// Edit packages/global-packages/packages/dev-tools/root-configs/eslint.config.blockera-site-toolkit.cjs
// project:bootstrap copies this to the host repo root for --project=blockera-site-toolkit.
const {
	createConfig,
} = require( './packages/global-packages/packages/dev-tools/js/eslint/config' );

module.exports = createConfig( {
	allowedTextDomains: [ 'blockera', 'blockera-site-toolkit' ],
} );
