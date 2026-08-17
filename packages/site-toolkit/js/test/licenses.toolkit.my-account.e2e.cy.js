/**
 * My Account → Licenses endpoint (PHP view + JS LicenseManager mount).
 *
 * @category my-account
 */

import { goTo, isWooCommerceActive, visitFront } from './helpers';

describe('Site Toolkit My Account licenses', () => {
	beforeEach(function () {
		isWooCommerceActive().then((active) => {
			if (!active) {
				this.skip();
			}
		});
	});

	it('should list Licenses in the My Account navigation', () => {
		visitFront('/my-account/');

		cy.get('.woocommerce-MyAccount-navigation, .woocommerce-account', {
			timeout: 20000,
		}).should('exist');

		cy.contains(
			'.woocommerce-MyAccount-navigation a, .woocommerce-MyAccount-navigation-link a',
			/Licenses/i
		).should('exist');
	});

	it('should render licenses endpoint with theme chrome and account shell', () => {
		visitFront('/my-account/licenses/');

		// Theme header/footer (classic or block theme).
		cy.get('body').should('exist');
		cy.get('header, .wp-site-blocks > header, #masthead').should('exist');
		cy.get('footer, .wp-site-blocks > footer, #colophon').should('exist');

		cy.get('.woocommerce-account, .woocommerce-MyAccount-content', {
			timeout: 20000,
		}).should('exist');

		cy.location('pathname').should('include', '/my-account/licenses');
	});

	it('should mount the license manager root when license payload is present', () => {
		visitFront('/my-account/licenses/');

		cy.window().then((win) => {
			const licenses = win.blockeraSiteToolkitLicenses;

			if (!licenses || !licenses.length) {
				// Empty / YITH-missing states still must not crash the page.
				cy.get('#blockera-site-toolkit-subscription-manager').should(
					'not.exist'
				);
				cy.get(
					'.woocommerce-info, .ywsbs-my-subscriptions, .woocommerce-MyAccount-content'
				).should('exist');
				return;
			}

			cy.get('#blockera-site-toolkit-subscription-manager', {
				timeout: 20000,
			}).should('exist');
			cy.get('.license-manager', { timeout: 20000 }).should('exist');
			cy.get('.product-header, .product-title').should('exist');
		});
	});

	it('should keep assets available on the licenses endpoint', () => {
		visitFront('/my-account/licenses/');

		cy.get('script[src*="site-toolkit"]', { timeout: 20000 }).should(
			'have.length.at.least',
			1
		);
	});

	it('should open the licenses page from wp-admin customer view path', () => {
		// Sanity: admin session still works alongside WooCommerce account pages.
		goTo('/wp-admin/index.php');
		cy.get('#wpadminbar').should('exist');

		visitFront('/my-account/licenses/');
		cy.location('pathname').should('include', '/my-account/licenses');
	});
});
