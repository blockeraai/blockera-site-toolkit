<?php

namespace BlockeraAI\SiteToolkit\Http\Controller;

use BlockeraAI\SiteToolkit\Repositories\ProductRepository;

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
        // Prevent autosave and revision handling.
        if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($postId)) {
            return;
        }

        // Check if it's a variable product.
        $product = wc_get_product($postId);
        if (!$product || !$product->is_type('variable')) {
            return;
        }

		// Creating the downloadable file token while updated or created the downloadable file as a meta data.
		if (!get_post_meta($postId, 'product_downloadable_token', true)) {
			update_post_meta(
				$postId,
				'product_downloadable_token',
				wp_generate_uuid4()
			);
		}

		// Upload general file to remote server.
		$this->uploadGeneralFileToRemoteServer($postId);

        // Get all variations.
        $variations = $product->get_children();

        array_map(function ($variationId) use ($postId) {
            $this->savingVariation($variationId, $postId);
        }, $variations);
    }

    /**
     * Saving variation.
     *
     * @param int $variationId The variation id.
     * @param int $postId The post id.
     *
     * @return void
     */
    protected function savingVariation(int $variationId, int $postId): void
    {
        $variation = wc_get_product($variationId);
        if (!$variation) {
            return;
        }

        // Get variation object
        $variation = wc_get_product($variationId);
        if (!$variation) {
            return;
        }

        array_map(function ($download) use ($variationId, $postId) {
            $this->uploadFileToRemoteServer($download, $variationId, $postId);
        }, $variation->get_data()['downloads'] ?? []);
    }

	/**
	 * Upload general file to remote server.
	 *
	 * @param int $postId The post id.
	 *
	 * @return void
	 */
	protected function uploadGeneralFileToRemoteServer(int $postId): void
	{
		$product = wc_get_product($postId);
		if (!$product) {
			return;
		}

		$fileUrl = $product->get_meta('product_download_file');

		if (empty($fileUrl)) {
			return;
		}

		$file = str_replace(get_site_url() . '/', ABSPATH, $fileUrl);

		// Throw error if file is empty or not exists.
		if (empty($file) || !file_exists($file)) {
			return;
		}

		// Get current user credentials
		$userCredentials = bsaGetUserAccessToken(null, false);
		if (empty($userCredentials)) {
			return;
		}

		// Upload file to remote server.
		$boundary = wp_generate_password(24);
		$payload = '';

		// Add text fields
		$payload .= '--' . $boundary . "\r\n";
		$payload .= 'Content-Disposition: form-data; name="token"' . "\r\n\r\n";
		$payload .= get_post_meta($postId, 'product_downloadable_token', true) . "\r\n";

		$payload .= '--' . $boundary . "\r\n";
		$payload .= 'Content-Disposition: form-data; name="name"' . "\r\n\r\n";
		$payload .= basename($file) . "\r\n";

		$payload .= '--' . $boundary . "\r\n";
		$payload .= 'Content-Disposition: form-data; name="product_id"' . "\r\n\r\n";
		$payload .= $postId . "\r\n";

		// Add file
		$payload .= '--' . $boundary . "\r\n";
		$payload .= 'Content-Disposition: form-data; name="file"; filename="' . basename($file) . '"' . "\r\n";
		$payload .= 'Content-Type: application/octet-stream' . "\r\n\r\n";
		$payload .= file_get_contents($file) . "\r\n";

		$payload .= '--' . $boundary . '--';

		$response = wp_remote_post(
			bsaGetConfig('BSA_API_BASE_URL') . '/files/v1/upload',
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
			// Delete the file from the WordPress uploads directory.
			$this->deleteFile($fileUrl);

			return;
		}

		// Skip if request failed.
		if (is_wp_error($response) || $status !== 201) {
			$body = json_decode(wp_remote_retrieve_body($response), true);
			wp_die(implode(', ', iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveArrayIterator($body['errors'])))));
		}
	}

    /**
     * Upload file to remote server.
     *
     * @param \WC_Product_Download $download The download object.
     * @param int $variationId The variation id.
     * @param int $postId The post id.
     *
     * @return void
     */
    protected function uploadFileToRemoteServer(\WC_Product_Download $download, int $variationId, int $postId): void
    {
        $fileUrl = $download->get_file();
        $file = str_replace(get_site_url() . '/', ABSPATH, $fileUrl);

        // Skip if file is disabled.
        if (!$download->get_enabled()) {
            return;
        }

        // Throw error if file is empty or not exists.
        if (empty($file) || !file_exists($file)) {
            return;
        }

        // Get current user credentials
        $userCredentials = bsaGetUserAccessToken(null, false);
        if (empty($userCredentials)) {
            return;
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
        $payload .= 'Content-Disposition: form-data; name="variation_id"' . "\r\n\r\n";
        $payload .= $variationId . "\r\n";

        $payload .= '--' . $boundary . "\r\n";
        $payload .= 'Content-Disposition: form-data; name="product_id"' . "\r\n\r\n";
        $payload .= $postId . "\r\n";

        // Add file
        $payload .= '--' . $boundary . "\r\n";
        $payload .= 'Content-Disposition: form-data; name="file"; filename="' . basename($file) . '"' . "\r\n";
        $payload .= 'Content-Type: application/octet-stream' . "\r\n\r\n";
        $payload .= file_get_contents($file) . "\r\n";

        $payload .= '--' . $boundary . '--';

        $response = wp_remote_post(
            bsaGetConfig('BSA_API_BASE_URL') . '/files/v1/upload',
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
			// Delete the file from the WordPress uploads directory.
			$this->deleteFile($fileUrl);

            return;
        }

        // Skip if request failed.
        if (is_wp_error($response) || $status !== 201) {
            $body = json_decode(wp_remote_retrieve_body($response), true);

            wp_die(implode(', ', iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveArrayIterator($body['errors'])))));
        }
    }

	/**
	 * Delete file from internal server.
	 *
	 * @param string $fileUrl The file url.
	 *
	 * @return void
	 */
	protected function deleteFile(string $fileUrl): void
	{
		$mediaId = attachment_url_to_postid($fileUrl);
		wp_delete_post($mediaId);
	}
}
