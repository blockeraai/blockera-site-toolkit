/**
 * Shared helpers for Blockera Site Toolkit Cypress E2E specs.
 *
 * Keep this file free of `@blockera/dev-cypress/js/helpers` barrel imports —
 * that barrel pulls editor/controls helpers and breaks Cypress bundling for
 * non-editor consumers.
 */

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
 * Resolve an absolute URL from the Cypress test site base.
 *
 * @param {string} path Path beginning with / or absolute URL.
 * @return {string}
 */
export function resolveTestUrl(path = '/') {
	if (/^https?:\/\//i.test(path)) {
		return path;
	}

	const testURL = Cypress.env('testURL') || getTestUrl();

	if (
		(testURL.endsWith('/') && !path.startsWith('/')) ||
		(!testURL.endsWith('/') && path.startsWith('/'))
	) {
		return `${testURL}${path}`;
	}

	if (!testURL.endsWith('/') && !path.startsWith('/')) {
		return `${testURL}/${path}`;
	}

	if (testURL.endsWith('/') && path.startsWith('/')) {
		return `${testURL.slice(0, -1)}${path}`;
	}

	return `${testURL}${path}`;
}

/**
 * Visit a path on the test site.
 *
 * Signature matches `@blockera/dev-cypress` `goTo`, but does not depend on
 * block-editor `getWPDataObject()` (site-toolkit specs are front/admin PHP UI).
 *
 * @param {string}  path  URI path.
 * @param {boolean} login When true, skip editor data-store waits.
 * @return {Cypress.Chainable}
 */
export function goTo(path = '/wp-admin', login = false) {
	return cy.visit(resolveTestUrl(path)).then(() => {
		return login ? cy.window().then((win) => win) : cy.window();
	});
}

/**
 * Visit a front-end (non-block-editor) path.
 *
 * @param {string} path Path beginning with /.
 * @return {Cypress.Chainable}
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
 * @return {Cypress.Chainable}
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
	return goTo('/wp-admin/index.php', true).then(() =>
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
