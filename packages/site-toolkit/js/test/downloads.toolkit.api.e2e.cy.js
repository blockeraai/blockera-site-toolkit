/**
 * File download REST endpoint (PHP FileController).
 *
 * @category api
 */

import { goTo, wpRest } from './helpers';

describe('Site Toolkit downloads API', () => {
	it('should reject download when bearer token is missing required fields', () => {
		wpRest('/auth/v1/download', {
			method: 'POST',
			headers: {
				Authorization: 'Bearer test-token',
				'Content-Type': 'application/json',
			},
			body: {},
		}).then((response) => {
			expect(response.status).to.eq(400);
			expect(response.body).to.include({
				code: 400,
				success: false,
			});
			expect(response.body.errors).to.be.an('object');
			expect(response.body.errors).to.include.all.keys(
				'invalid_token',
				'invalid_name'
			);
		});
	});
});
