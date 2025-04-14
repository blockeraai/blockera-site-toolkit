<?php

namespace BlockeraAI\SiteToolkit\Meta;

use Blockera\Utils\View;

class Factory
{
    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct()
    {
        // Variation Custom Fields.
        add_action('woocommerce_product_after_variable_attributes', [$this, 'addVariationCustomFields'], 10, 3);
        add_action('woocommerce_save_product_variation', [$this, 'saveVariationCustomFields'], 10, 2);
        add_filter('woocommerce_add_cart_item_data', [$this, 'addVariationDataToCart'], 10, 3);
        add_action('woocommerce_before_add_to_cart_button', [$this, 'displayVariationCustomFields']);
        add_filter('woocommerce_available_variation', [$this, 'addCustomFieldsToVariationData']);

        // Product Custom Fields.
        add_action('add_meta_boxes', [$this, 'AddProductCustomFields']);
        add_action('save_post_product', [$this, 'saveProductCustomFields']);
        add_action('woocommerce_single_product_summary', [$this, 'displayProductCustomFields'], 25);
    }

    /**
     * Add Product Custom Fields.
     *
     * @return void
     */
    public function AddProductCustomFields()
    {
        add_meta_box(
            'product_extra_fields', // Meta box ID.
            'Product Additional Information', // Meta box title.
            [$this, 'productCustomFieldsContent'], // Callback function.
            'product', // Post type (WooCommerce products).
            'normal', // Context.
            'high' // Priority.
        );
    }

    /**
     * Product Custom Fields Content.
     *
     * @param \WP_Post $post The post object.
     * @return void
     */
    public function productCustomFieldsContent($post)
    {
        // Add nonce for security.
        wp_nonce_field('product_custom_fields', 'product_custom_fields_nonce');

        // Get existing values.
		$product_id = get_post_meta($post->ID, 'product_id', true);
        $product_version = get_post_meta($post->ID, 'product_version', true);
        $product_color = get_post_meta($post->ID, 'product_color', true);
		$product_download_file = get_post_meta($post->ID, 'product_download_file', true);

        View::load('product-meta-box-custom-fields', 
			compact('product_id','product_version', 'product_color', 'product_download_file'),
			[
				'root-path' => trailingslashit(__DIR__),
			]
		);
    }

    /**
     * Save Product Custom Fields.
     *
     * @param int $post_id The post ID.
     * @return void
     */
    public function saveProductCustomFields($post_id)
    {
        // Check if nonce is set.
        if (!isset($_POST['product_custom_fields_nonce'])) {
            return;
        }

        // Verify nonce.
        if (!wp_verify_nonce($_POST['product_custom_fields_nonce'], 'product_custom_fields')) {
            return;
        }

		// Save product id.
        if (isset($_POST['product_id'])) {
            update_post_meta(
                $post_id,
                'product_id',
                sanitize_text_field($_POST['product_id'])
            );
        }

        // Save product version.
        if (isset($_POST['product_version'])) {
            update_post_meta(
                $post_id,
                'product_version',
                sanitize_text_field($_POST['product_version'])
            );
        }

        // Save product color.
        if (isset($_POST['product_color'])) {
            update_post_meta(
                $post_id,
                'product_color',
                sanitize_hex_color($_POST['product_color'])
            );
        }

		// Save product download file.
		if (isset($_POST['product_download_file'])) {
			update_post_meta(
				$post_id,
				'product_download_file',
				sanitize_text_field($_POST['product_download_file'])
			);
		}
    }

    /**
     * Display Product Custom Fields.
     *
     * @return void
     */
    public function displayProductCustomFields()
    {
        global $product;

        if ($product) {
			// Product ID.
            $product_id = get_post_meta($product->get_id(), 'product_id', true);

			if ($product_id) {
				echo '<div class="product-id">ID: ' . esc_html($product_id) . '</div>';
			}

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

    /**
     * Add Variation Custom Fields.
     *
     * @param int $loop The loop index.
     * @param array $variation_data The variation data.
     * @param \WP_Post $variation The variation post object.
     * @return void
     */
    public function addVariationCustomFields($loop, $variation_data, $variation)
    {
        // Max Domains field.
        woocommerce_wp_text_input(array(
            'id' => 'max_domains[' . $loop . ']',
            'name' => 'max_domains[' . $loop . ']',
            'label' => 'Max Domains',
            'type' => 'number',
            'value' => get_post_meta($variation->ID, 'max_domains', true),
            'wrapper_class' => 'form-row form-row-full'
        ));
    }

    /**
     * Save Variation Custom Fields.
     *
     * @param int $variation_id The variation ID.
     * @param int $loop The loop index.
     * @return void
     */
    public function saveVariationCustomFields($variation_id, $loop)
    {
        // Save Max Domains.
        if (isset($_POST['max_domains'][$loop])) {
            update_post_meta(
                $variation_id,
                'max_domains',
                absint($_POST['max_domains'][$loop])
            );
        }
    }

    /**
     * Add Variation Data to Cart.
     *
     * @param array $cart_item_data The cart item data.
     * @param int $product_id The product ID.
     * @param int $variation_id The variation ID.
     * @return array
     */
    public function addVariationDataToCart($cart_item_data, $product_id, $variation_id)
    {
        if ($variation_id) {
            $max_domains = get_post_meta($variation_id, 'max_domains', true);

            if ($max_domains) {
                $cart_item_data['max_domains'] = $max_domains;
            }
        }
        return $cart_item_data;
    }

    /**
     * Display Variation Custom Fields.
     *
     * @return void
     */
    public function displayVariationCustomFields()
    {
        global $product;

        if ($product && $product->is_type('variable')) {
			View::load('product-variation-custom-fields', [], [
				'root-path' => trailingslashit(__DIR__),
			]);
        }
    }

    /**
     * Add Custom Fields to Variation Data.
     *
     * @param array $variationData The variation data.
     * @return array
     */
    public function addCustomFieldsToVariationData(array $variationData)
    {
        $variationData['max_domains'] = get_post_meta($variationData['variation_id'], 'max_domains', true);

        return $variationData;
    }
}
