<?php

namespace BlockeraAI\SiteToolkit\Http\Controller;

use BlockeraAI\SiteToolkit\Repositories\ProductRepository;
use BlockeraAI\SiteToolkit\Repositories\SubscriptionRepository;

class ProductController
{
	/**
	 * Check if the user has permission to access the license manager.
	 * Validates nonce, handles cancellation, and verifies access token.
	 *
	 * @param \WP_REST_Request $request The request object.
	 *
	 * @return bool true on success, false on otherwise!
	 */
	public function permission(\WP_REST_Request $request): bool
	{
		return true;
	}

	/**
	 * Get the allowed plans for the product.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response The response object.
	 */
	public function allowedPlans(\WP_REST_Request $request): \WP_REST_Response
	{
		if (empty($request->get_param('id'))) {
			return new \WP_REST_Response([
				'code' => 400,
				'success' => false,
				'message' => 'Product ID is required',
			], 400);
		}

		$productRepository = new ProductRepository();

		$product = $productRepository->getById((int)$request->get_param('id'));

		if (!$product) {
			return new \WP_REST_Response([
				'code' => 404,
				'success' => false,
				'message' => 'Product not found',
			], 404);
		}

		$subscriptions = $productRepository->getProductVariations($product->ID);

		return new \WP_REST_Response([
			'code' => 200,
			'success' => true,
			'data' => $subscriptions,
		], 200);
	}

	/**
	 * Save the product.
	 *
	 * @param int $postId The post id.
	 * @param \WP_Post $post The post object.
	 *
	 * @return void
	 */
	public function save(int $postId, \WP_Post $post, $update): void
	{
		// Prevent autosave and revision handling
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
		if (wp_is_post_revision($postId)) return;

		// Check if it's a variable product
		$product = wc_get_product($postId);
		if (!$product || !$product->is_type('variable')) return;


		$subscriptionRepository = new SubscriptionRepository();

		$subscription_id = $subscriptionRepository->getSubscriptionIdByProductId($postId);

		if (!$subscription_id) {
			throw new \Exception('Subscription not found for product: ' . $postId);
		}

		// Get all variations
		$variations = $product->get_children();

		foreach ($variations as $variation_id) {
			// Get variation object
			$variation = wc_get_product($variation_id);
			if (!$variation) continue;

			foreach ($variation->get_data()['downloads'] ?? [] as $download) {
				/**
				 * @var \WC_Product_Download $download
				 */
				$file_url = $download->get_file();
				$file = str_replace(get_site_url() . '/', ABSPATH, $file_url);

				// Skip if file is disabled.
				if (!$download->get_enabled()) {
					continue;
				}

				// Throw error if file is empty or not exists.
				if (empty($file) || !file_exists($file)) {
					throw new \Exception('File not found: ' . $file . ' for product: ' . $postId . ' and variation: ' . $variation_id);
				}

				// Get current user credentials
				$userCredentials = bsaGetUserAccessToken();
				if (empty($userCredentials)) {
					continue;
				}

				// Upload file to remote server
				$boundary = wp_generate_password(24);
				$payload = '';

				// Add text fields
				$payload .= '--' . $boundary . "\r\n";
				$payload .= 'Content-Disposition: form-data; name="token"' . "\r\n\r\n";
				$payload .= $download->get_id() . "\r\n";

				$payload .= '--' . $boundary . "\r\n";
				$payload .= 'Content-Disposition: form-data; name="name"' . "\r\n\r\n";
				$payload .= $download->get_name() . "\r\n";

				$payload .= '--' . $boundary . "\r\n";
				$payload .= 'Content-Disposition: form-data; name="subscription_id"' . "\r\n\r\n";
				$payload .= $subscription_id . "\r\n";

				// Add file
				$payload .= '--' . $boundary . "\r\n";
				$payload .= 'Content-Disposition: form-data; name="file"; filename="' . basename($file) . '"' . "\r\n";
				$payload .= 'Content-Type: application/octet-stream' . "\r\n\r\n";
				$payload .= file_get_contents($file) . "\r\n";

				$payload .= '--' . $boundary . '--';

				$response = wp_remote_post(
					bsaGetConfig('BSA_API_BASE_URL') . '/downloads-manager/v1/upload',
					[
						'timeout' => 30,
						'sslverify' => false,
						'headers' => [
							'Authorization' => $userCredentials['token_type'] . ' ' . $userCredentials['access_token'],
							'Accept' => 'application/json',
							'Content-Type' => 'multipart/form-data; boundary=' . $boundary
						],
						'body' => $payload
					]
				);

				$status = wp_remote_retrieve_response_code($response);

				// Occurs when the file already exists.
				if (200 === $status) {
					continue;
				}

				// Skip if request failed.
				if (is_wp_error($response) || $status !== 201) {
					$body = json_decode(wp_remote_retrieve_body($response), true);

					throw new \Exception(implode(', ', $body['errors']));
				}
			}
		}
	}
}
