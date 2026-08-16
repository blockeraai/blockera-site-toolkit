/**
 * OAuth authorize rewrite + login gate (PHP Routes/web.php).
 *
 * @category oauth
 */

import { goTo } from '@blockera/dev-cypress/js/helpers';
import { visitFront } from './helpers';

describe('Site Toolkit authorize OAuth entry', () => {
	it('should redirect guests from /authorize to wp-login', () => {
		cy.logout();
		visitFront('/authorize/?response_type=code&state=e2e');

		cy.location('href', { timeout: 20000 }).should(
			'include',
			'wp-login.php'
		);
		cy.location('href').should('match', /redirect_to=/);
	});

	it('should keep logged-in users on authorize flow without a fatal error', () => {
		goTo('/wp-admin/index.php');
		cy.get('#wpadminbar').should('exist');

		visitFront('/authorize/?response_type=code&state=e2e-logged-in');

		// Logged-in authorize either redirects to consent-form or stays without WP die.
		cy.location('href', { timeout: 20000 }).should(
			'not.include',
			'wp-login.php'
		);
		cy.get('body').should('not.contain', 'There has been a critical error');
	});
});
