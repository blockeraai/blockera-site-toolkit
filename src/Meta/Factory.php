<?php

namespace BlockeraAI\SiteToolkit\Meta;

class Factory
{
	public function __construct()
	{
		// Variation Custom Fields
		add_action('woocommerce_product_after_variable_attributes', [$this, 'AddVariationCustomFields'], 10, 3);
		add_action('woocommerce_save_product_variation', [$this, 'SaveVariationCustomFields'], 10, 2);
		add_filter('woocommerce_add_cart_item_data', [$this, 'AddVariationDataToCart'], 10, 3);
		add_action('woocommerce_before_add_to_cart_button', [$this, 'DisplayVariationCustomFields']);
		add_filter('woocommerce_available_variation', [$this, 'AddCustomFieldsToVariationData']);

		// Product Custom Fields
		add_action('add_meta_boxes', [$this, 'AddProductCustomFields']);
		add_action('save_post_product', [$this, 'SaveProductCustomFields']);
		add_action('woocommerce_single_product_summary', [$this, 'DisplayProductCustomFields'], 25);
	}

	public function AddProductCustomFields()
	{
		add_meta_box(
			'product_extra_fields', // Meta box ID
			'Product Additional Information', // Meta box title
			[$this, 'ProductCustomFieldsContent'], // Callback function
			'product', // Post type (WooCommerce products)
			'normal', // Context
			'high' // Priority
		);
	}

	public function ProductCustomFieldsContent($post)
	{
		// Add nonce for security
		wp_nonce_field('product_custom_fields', 'product_custom_fields_nonce');

		// Get existing values
		$product_version = get_post_meta($post->ID, 'product_version', true);
		$product_color = get_post_meta($post->ID, 'product_color', true);
?>
		<div class="product-custom-fields">
			<p>
				<label for="product_version">Product Version:</label>
				<input type="text" id="product_version" name="product_version"
					value="<?php echo esc_attr($product_version); ?>" />
			</p>
			<p>
				<label for="product_color">Product Color:</label>
				<input type="color" id="product_color" name="product_color"
					value="<?php echo esc_attr($product_color); ?>" />
			</p>
		</div>
		<?php
	}

	public function SaveProductCustomFields($post_id)
	{
		// Check if nonce is set
		if (!isset($_POST['product_custom_fields_nonce'])) {
			return;
		}

		// Verify nonce
		if (!wp_verify_nonce($_POST['product_custom_fields_nonce'], 'product_custom_fields')) {
			return;
		}

		// Save product version
		if (isset($_POST['product_version'])) {
			update_post_meta(
				$post_id,
				'product_version',
				sanitize_text_field($_POST['product_version'])
			);
		}

		// Save product color
		if (isset($_POST['product_color'])) {
			update_post_meta(
				$post_id,
				'product_color',
				sanitize_hex_color($_POST['product_color'])
			);
		}
	}

	public function DisplayProductCustomFields()
	{
		global $product;

		if ($product) {
			$product_version = get_post_meta($product->get_id(), 'product_version', true);

			if ($product_version) {
				echo '<div class="product-version">Version: ' . esc_html($product_version) . '</div>';
			}

			$product_color = get_post_meta($product->get_id(), 'product_color', true);

			if ($product_color) {
				echo '<div class="product-color" style="background-color: ' . esc_attr($product_color) . ';">Color: ' . esc_html($product_color) . '</div>';
			}
		}
	}

	public function AddVariationCustomFields($loop, $variation_data, $variation)
	{
		// Max Domains field
		woocommerce_wp_text_input(array(
			'id' => 'max_domains[' . $loop . ']',
			'name' => 'max_domains[' . $loop . ']',
			'label' => 'Max Domains',
			'type' => 'number',
			'value' => get_post_meta($variation->ID, 'max_domains', true),
			'wrapper_class' => 'form-row form-row-full'
		));
	}

	public function SaveVariationCustomFields($variation_id, $loop)
	{
		// Save Max Domains
		if (isset($_POST['max_domains'][$loop])) {
			update_post_meta(
				$variation_id,
				'max_domains',
				absint($_POST['max_domains'][$loop])
			);
		}
	}

	public function AddVariationDataToCart($cart_item_data, $product_id, $variation_id)
	{
		if ($variation_id) {
			$max_domains = get_post_meta($variation_id, 'max_domains', true);

			if ($max_domains) {
				$cart_item_data['max_domains'] = $max_domains;
			}
		}
		return $cart_item_data;
	}

	public function DisplayVariationCustomFields()
	{
		global $product;

		if ($product && $product->is_type('variable')) {
		?>
			<div class="variation-custom-fields" style="display: none;">
				<div class="max-domains"></div>
			</div>

			<script type="text/javascript">
				jQuery(document).ready(function($) {
					$('form.variations_form').on('show_variation', function(event, variation) {
						if (variation.max_domains) {
							$('.variation-custom-fields .max-domains')
								.html('Maximum Domains: ' + variation.max_domains);
						}
						$('.variation-custom-fields').show();
					});

					$('form.variations_form').on('hide_variation', function() {
						$('.variation-custom-fields').hide();
					});
				});
			</script>
<?php
		}
	}

	public function AddCustomFieldsToVariationData(array $variationData)
	{
		$variationData['max_domains'] = get_post_meta($variationData['variation_id'], 'max_domains', true);

		return $variationData;
	}
}
