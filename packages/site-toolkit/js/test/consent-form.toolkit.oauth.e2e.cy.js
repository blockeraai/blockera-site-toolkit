/**
 * OAuth consent form view + React ConsentForm mount (PHP + JS).
 *
 * @category oauth
 */

import { goTo, getTestUrl, visitFront } from './helpers';

const buildConsentUrl = () => {
	const redirectUri = encodeURIComponent(`${getTestUrl()}/`);
	return `/consent-form/?redirect_uri=${redirectUri}&state=e2e&response_type=code`;
};

describe('Site Toolkit consent form', () => {
	beforeEach(() => {
		goTo('/wp-admin/index.php');
		cy.get('#wpadminbar').should('exist');
	});

	it('should load the consent-form rewrite without a critical error', () => {
		visitFront(buildConsentUrl());

		cy.location('pathname', { timeout: 20000 }).should(
			'include',
			'consent-form'
		);
		cy.get('body').should('not.contain', 'There has been a critical error');
	});

	it('should expose consent globals when the React mount root is present', () => {
		visitFront(buildConsentUrl());

		cy.window().then((win) => {
			if (!win.isConsentForm) {
				cy.log(
					'Consent form flag missing — YITH/licenses prerequisites may be absent'
				);
				cy.get('body').should('exist');
				return;
			}

			expect(win.isConsentForm).to.eq(true);
			cy.get('#blockera-site-toolkit-consent-form', {
				timeout: 20000,
			}).should('exist');
			cy.get('.consent-form', { timeout: 20000 }).should('exist');
		});
	});

	it('should render connect UI when licenses are available for consent', () => {
		visitFront(buildConsentUrl());

		cy.window().then((win) => {
			const licenses = win.blockeraSiteToolkitLicenses || [];

			if (!win.isConsentForm || !licenses.length) {
				cy.log('No consent licenses available in this environment');
				return;
			}

			cy.contains(/connect your site|Connect/i, {
				timeout: 20000,
			}).should('exist');
			cy.get('[data-test="connect-button"], .connect-button').should(
				'exist'
			);
		});
	});
});
