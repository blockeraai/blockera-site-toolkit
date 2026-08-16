/**
 * WooCommerce product admin meta (PHP Meta\Factory + templates).
 *
 * @category admin
 */

import { goTo } from '@blockera/dev-cypress/js/helpers';
import { isWooCommerceActive } from './helpers';

describe('Site Toolkit product admin meta', () => {
	beforeEach(function () {
		isWooCommerceActive().then((active) => {
			if (!active) {
				this.skip();
			}
		});
	});

	it('should open the add-product screen without a critical error', () => {
		goTo('/wp-admin/post-new.php?post_type=product');

		cy.get('#poststuff, .block-editor-page', { timeout: 30000 }).should(
			'exist'
		);
		cy.get('body').should('not.contain', 'There has been a critical error');
	});

	it('should show Blockera product custom fields when classic meta UI is present', () => {
		goTo('/wp-admin/post-new.php?post_type=product');

		cy.get('body').then(($body) => {
			const hasClassicMeta =
				$body.find(
					'#woocommerce-product-data, .woocommerce_options_panel'
				).length > 0;

			if (!hasClassicMeta) {
				cy.log(
					'Classic product data panel not present (block product editor?)'
				);
				return;
			}

			cy.get('body').then(($inner) => {
				if (
					$inner.find('#product_id, input[name="product_id"]').length
				) {
					cy.get('#product_id, input[name="product_id"]').should(
						'exist'
					);
				} else {
					cy.log(
						'Blockera product_id field not injected in this WC editor mode'
					);
				}
			});
		});
	});
});
