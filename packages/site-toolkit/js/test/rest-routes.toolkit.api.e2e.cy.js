/**
 * REST route registration and auth gate coverage (PHP controllers).
 *
 * @category api
 */

import { getWpRestNonce, wpRest } from './helpers';

const LICENSE_ROUTES = [
	'/auth/v1/licenses',
	'/auth/v1/licenses/create',
	'/auth/v1/license/delete',
	'/auth/v1/license/renew',
	'/auth/v1/license/upgrade',
	'/auth/v1/download',
	'/auth/v1/products/allowed-plans',
	'/release/v1/product',
];

describe('Site Toolkit REST API surface', () => {
	it('should register license, download, and product routes on the index', () => {
		wpRest('/').then((response) => {
			expect(response.status).to.eq(200);

			const routes = Object.keys(response.body?.routes || {});

			LICENSE_ROUTES.forEach((route) => {
				expect(
					routes.some(
						(registered) =>
							registered === route ||
							registered.startsWith(`${route}/`) ||
							registered.includes(route.replace(/^\//, ''))
					),
					`expected route registration for ${route}`
				).to.eq(true);
			});
		});
	});

	it('should reject license list without bearer or site nonce', () => {
		wpRest('/auth/v1/licenses', {
			method: 'GET',
			qs: { client_id: '00000000-0000-4000-8000-000000000000' },
		}).then((response) => {
			expect(response.status).to.be.oneOf([401, 403]);
		});
	});

	it('should reject license create without bearer or site nonce', () => {
		wpRest('/auth/v1/licenses/create', {
			method: 'POST',
			body: {},
		}).then((response) => {
			expect(response.status).to.be.oneOf([401, 403]);
		});
	});

	it('should reject license delete without bearer or site nonce', () => {
		wpRest('/auth/v1/license/delete', {
			method: 'POST',
			body: { domain: 'example.test', domain_id: '1' },
		}).then((response) => {
			expect(response.status).to.be.oneOf([401, 403]);
		});
	});

	it('should reject download without bearer or site nonce', () => {
		wpRest('/auth/v1/download', {
			method: 'POST',
			body: { token: 'x', name: 'file.zip' },
		}).then((response) => {
			expect(response.status).to.be.oneOf([401, 403]);
		});
	});

	it('should reject referer-based license calls with an invalid toolkit nonce', () => {
		getWpRestNonce().then(() => {
			wpRest('/auth/v1/licenses', {
				method: 'GET',
				qs: { client_id: '00000000-0000-4000-8000-000000000000' },
				headers: {
					Referer: Cypress.env('testURL') || 'http://localhost:8888',
					'X-Blockera-Nonce': 'invalid-nonce',
				},
			}).then((response) => {
				expect(response.status).to.be.oneOf([401, 403]);
			});
		});
	});
});
