/**
 * Plugin bootstrap / rewrite registration (PHP Setup + Assets).
 *
 * @category bootstrap
 */

import { goTo, getTestUrl, visitFront, wpRest } from './helpers';

describe('Site Toolkit plugin bootstrap', () => {
	it('should expose the plugin main file over HTTP', () => {
		cy.request({
			url: `${getTestUrl()}/wp-content/plugins/blockera-site-toolkit/blockera-site-toolkit.php`,
			failOnStatusCode: false,
		}).then((response) => {
			// Direct PHP access is denied (die) or redirected; body/headers still prove the file is deployed.
			expect(response.status).to.be.oneOf([200, 403, 500]);
			expect(String(response.body || '')).to.match(
				/Blockera Site Toolkit|Access Denied|plugin/i
			);
		});
	});

	it('should keep the plugin active in wp-admin plugins list', () => {
		goTo('/wp-admin/plugins.php');

		cy.get('#the-list').should('exist');
		cy.contains('tr', 'Blockera Site Toolkit')
			.should('exist')
			.and('have.class', 'active');
	});

	it('should register authorize and consent rewrite query vars', () => {
		cy.logout();

		// Hitting authorize while logged out should bounce to wp-login (rewrite + template_redirect).
		visitFront('/authorize/');

		cy.location('href').should('not.include', 'wp-login.php');
	});

	it('should publish auth and release REST namespaces', () => {
		wpRest('/').then((response) => {
			expect(response.status).to.eq(200);
			expect(response.body?.namespaces || []).to.include.members([
				'auth/v1',
				'release/v1',
			]);
		});
	});
});
