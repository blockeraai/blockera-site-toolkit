/**
 * Shared helpers for Blockera Site Toolkit Cypress E2E specs.
 */

import { goTo } from '@blockera/dev-cypress/js/helpers';

/**
 * Absolute test site URL without trailing slash.
 *
 * @return {string}
 */
export function getTestUrl() {
	return (Cypress.env('testURL') || 'http://localhost:8888').replace(
		/\/$/,
		''
	);
}

/**
 * Visit a front-end (non-block-editor) path.
 * Uses login=true so goTo skips getWPDataObject().
 *
 * @param {string} path Path beginning with /.
 */
export function visitFront(path = '/') {
	return goTo(path, true);
}

/**
 * REST API root for the test site.
 *
 * @return {string}
 */
export function getWpJsonRoot() {
	return `${getTestUrl()}/wp-json`;
}

/**
 * Perform a wp-json request (does not fail on non-2xx by default).
 *
 * @param {string} path REST path (e.g. /auth/v1/licenses).
 * @param {object} options Cypress request options.
 */
export function wpRest(path, options = {}) {
	const normalized = path.startsWith('/') ? path : `/${path}`;

	return cy.request({
		url: `${getWpJsonRoot()}${normalized}`,
		failOnStatusCode: false,
		...options,
	});
}

/**
 * Read wpApiSettings.nonce after visiting wp-admin.
 *
 * @return {Cypress.Chainable<string>}
 */
export function getWpRestNonce() {
	return goTo('/wp-admin/index.php').then(() =>
		cy.window().then((win) => {
			const nonce = win?.wpApiSettings?.nonce;

			expect(nonce, 'wpApiSettings.nonce').to.be.a('string').and.not.be
				.empty;

			return nonce;
		})
	);
}

/**
 * Whether WooCommerce appears active on the site.
 *
 * @return {Cypress.Chainable<boolean>}
 */
export function isWooCommerceActive() {
	return wpRest('/wc/store/v1').then((response) => response.status !== 404);
}
