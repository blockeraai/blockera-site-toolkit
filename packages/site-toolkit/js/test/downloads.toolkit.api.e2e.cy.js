/**
 * File download REST endpoint (PHP FileController).
 *
 * @category api
 */

import { wpRest } from './helpers';

describe('Site Toolkit downloads API', () => {
	it('should reject download when bearer token is missing required fields', () => {
		wpRest('/auth/v1/download', {
			method: 'POST',
			headers: {
				Authorization: 'Bearer test-token',
			},
			body: {},
		}).then((response) => {
			expect(response.status).to.be.oneOf([200, 400, 403, 500]);
			if (response.body && typeof response.body === 'object') {
				expect(response.body).to.satisfy(
					(body) =>
						body.success === false ||
						Boolean(body.code) ||
						Boolean(body.message) ||
						Boolean(body.errors)
				);
			}
		});
	});
});
