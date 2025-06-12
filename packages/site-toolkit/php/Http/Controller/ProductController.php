<?php

namespace BlockeraAI\SiteToolkit\Http\Controller;

use BlockeraAI\SiteToolkit\Services\UploadService;
use BlockeraAI\SiteToolkit\Repositories\ProductRepository;

class ProductController
{
	/**
	 * The upload service.
	 *
	 * @var UploadService
	 */
	protected UploadService $uploadService;

	/**
	 * Set the upload service.
	 *
	 * @param UploadService $uploadService The upload service.
	 * @return void
	 */
	public function setUploadService(UploadService $uploadService): void
	{
		$this->uploadService = $uploadService;
	}

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

        $version = get_post_meta($postId, 'product_version', true);

        // Upload general file to remote server.
        $this->uploadGeneralFileToRemoteServer($postId, $version);

        // Get all variations.
        $variations = $product->get_children();

        array_map(function ($variationId) use ($postId, $version) {
            $this->savingVariation($variationId, $postId, $version);
        }, $variations);
    }

    /**
     * Upload general file to remote server.
     *
     * @param int $postId The post id.
     *
     * @return void
     */
    protected function uploadGeneralFileToRemoteServer(int $postId, string $version = ''): void
    {
        $product = wc_get_product($postId);
        if (!$product) {
            return;
        }

        $files = $product->get_meta('product_downloadable_files');

        if (empty($files)) {
            return;
        }

        foreach ($files as $key => $fileData) {

            $result = $this->uploadService->uploadFile($fileData, compact('postId', 'version'));

			if (empty($result['status'])) {
				continue;
			}

			if (!isset($result['data']['download_token'])) {
				continue;
			}

			if ($result['data']['download_token'] === $fileData['hash']) {
				continue;
			}

			$files[$key]['hash'] = $result['data']['download_token'];
			update_post_meta($postId, 'product_downloadable_files', $files);
        }
    }

	/**
     * Saving variation.
     *
     * @param int $variationId The variation id.
     * @param int $postId The post id.
     * @param string $version The version.
     *
     * @return void
     */
    protected function savingVariation(int $variationId, int $postId, string $version = ''): void
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

        array_map(function ($download) use ($variationId, $postId, $version) {
            $this->uploadFileOfVariationDownloads($download, compact('variationId', 'postId', 'version'));
        }, $variation->get_data()['downloads'] ?? []);
    }

    /**
     * Upload file to remote server from variation downloads.
     *
     * @param \WC_Product_Download $download The download object.
     * @param array $args {
     *     Array of arguments.
     *     @type int    $variationId The variation id.
     *     @type int    $postId     The post id.
     *     @type string $version    The version.
     * }
     *
     * @return void
     */
    protected function uploadFileOfVariationDownloads(\WC_Product_Download $download, array $args): void
    {
		extract($args);
        $fileUrl = $download->get_file();

        // Skip if file is disabled.
        if (!$download->get_enabled()) {
            return;
        }

		$this->uploadService->uploadFile([
			'file' => $fileUrl,
			'hash' => $download->get_id(),
		], compact('variationId', 'postId', 'version'));
    }
}
