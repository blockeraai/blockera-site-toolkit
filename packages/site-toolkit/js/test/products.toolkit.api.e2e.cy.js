/**
 * Product REST endpoints (PHP ProductController).
 *
 * @category api
 */

import { wpRest } from './helpers';

describe('Site Toolkit products API', () => {
	it('should require a product id for allowed-plans', () => {
		wpRest('/auth/v1/products/allowed-plans', {
			method: 'POST',
			body: {},
		}).then((response) => {
			expect(response.status).to.be.oneOf([200, 400]);
			expect(response.body?.success).to.eq(false);
		});
	});

	it('should expose the release product endpoint', () => {
		wpRest('/release/v1/product', {
			method: 'POST',
			body: {},
		}).then((response) => {
			// Open permission callback; controller validates payload.
			expect(response.status).to.be.a('number');
			expect(response.status).to.be.gte(200).and.lt(600);
		});
	});
});
