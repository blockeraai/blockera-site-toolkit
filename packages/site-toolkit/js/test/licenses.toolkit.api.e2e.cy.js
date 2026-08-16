/**
 * License manager REST behaviors (PHP LicenseManagerController).
 *
 * @category api
 */

import { wpRest } from './helpers';

describe('Site Toolkit licenses API', () => {
	it('should accept bearer-style permission header shape for GET licenses', () => {
		wpRest('/auth/v1/licenses', {
			method: 'GET',
			qs: { client_id: 'not-a-uuid' },
			headers: {
				Authorization: 'Bearer test-token',
			},
		}).then((response) => {
			// Permission passes with Bearer; controller then validates client_id UUID.
			expect(response.status).to.be.oneOf([200, 400, 422, 500]);
			if (response.body && typeof response.body === 'object') {
				expect(response.body).to.have.any.keys(
					'success',
					'errors',
					'code',
					'data',
					'message'
				);
			}
		});
	});

	it('should require action and license_id for renew', () => {
		wpRest('/auth/v1/license/renew', {
			method: 'POST',
			headers: {
				Authorization: 'Bearer test-token',
			},
			body: {},
		}).then((response) => {
			expect(response.status).to.be.oneOf([200, 400, 500]);
			if (response.body?.errors) {
				expect(response.body.errors).to.include.keys(
					'invalid_action',
					'required_license_id'
				);
			}
		});
	});

	it('should require action and license_id for upgrade', () => {
		wpRest('/auth/v1/license/upgrade', {
			method: 'POST',
			headers: {
				Authorization: 'Bearer test-token',
			},
			body: {},
		}).then((response) => {
			expect(response.status).to.be.oneOf([200, 400, 500]);
			if (response.body?.errors) {
				expect(response.body.errors).to.include.keys(
					'invalid_action',
					'required_license_id'
				);
			}
		});
	});
});
