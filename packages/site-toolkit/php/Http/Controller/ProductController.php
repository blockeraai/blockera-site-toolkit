<?php

namespace BlockeraAI\SiteToolkit\Http\Controller;

use BlockeraAI\SiteToolkit\Services\UploadService;
use BlockeraAI\SiteToolkit\Repositories\ProductRepository;

class ProductController {

	/**
	 * The upload service.
	 *
	 * @var UploadService
	 */
	protected UploadService $uploadService; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase -- Existing API property name.

	/**
	 * Set the upload service.
	 *
	 * @param UploadService $uploadService The upload service.
	 * @return void
	 */
	public function setUploadService( UploadService $uploadService): void {
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Existing API property name.
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
    public function permission( \WP_REST_Request $request): bool {
        return true;
    }

    /**
     * Get the allowed plans for the product.
     *
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response The response object.
     */
    public function allowedPlans( \WP_REST_Request $request): \WP_REST_Response {
        if (empty($request->get_param('id'))) {
            return new \WP_REST_Response(
                [
					'code' => 400,
					'success' => false,
					'message' => 'Product ID is required',
				],
                400
            );
        }

        $productRepository = new ProductRepository();

        $product = $productRepository->getById( (int) $request->get_param('id'));

        if (! $product) {
            return new \WP_REST_Response(
                [
					'code' => 404,
					'success' => false,
					'message' => 'Product not found',
				],
                404
            );
        }

        $subscriptions = $productRepository->getProductVariations($product->ID);

        return new \WP_REST_Response(
            [
				'code' => 200,
				'success' => true,
				'data' => $subscriptions,
			],
            200
        );
    }

	/**
	 * Release a new version of the product.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * 
	 * @return \WP_REST_Response The response object.
	 */
	public function releaseVersion( \WP_REST_Request $request): \WP_REST_Response {
		$allowedParams = [
			'action',
			'metaKey',
			'api_key',
			'filename',
			'product_id', 
			'product_version',
		];

		if (! array_intersect($allowedParams, array_keys($request->get_params()))) {
			return new \WP_REST_Response(
                [
					'code' => 400,
					'success' => false,
					'errors' => [
						'meta_keys' => __('Request parameters are not allowed', 'blockera-site-toolkit'),
					],
				],
                400
            );
		}

		$action    = $request->get_param('action');
		$apiKey    = $request->get_param('api_key');
		$metaKey   = $request->get_param('metaKey');
		$filename  = $request->get_param('filename');
		$version   = $request->get_param('product_version');
		$productId = (int) $request->get_param('product_id');

		// Get file from either file params or body params.
		$file = null;
		if ($request->get_file_params() && ! empty($request->get_file_params()['file'])) {
			$file = $request->get_file_params()['file'];
		} elseif ($request->get_body_params() && ! empty($request->get_body_params()['file'])) {
			$file = $request->get_body_params()['file'];
		}

		$errors = [];

		// Validate required fields.
		if (empty($action)) {
			$errors['action'] = __('Action is required', 'blockera-site-toolkit');
		}
		if (empty($action) || 'release-blockera-products-new-version' !== $action) {
			$errors['action'] = __('Action is not allowed', 'blockera-site-toolkit');
		}
		if (empty($apiKey)) {
			$errors['api_key'] = __('API key is required', 'blockera-site-toolkit');
		}
		// Validate API Key based on the user email.
		if (empty($apiKey) || md5('blockeraai+githubbot@gmail.com') !== $apiKey) {
			$errors['api_key'] = __('API key is invalid', 'blockera-site-toolkit');
		}
		if (empty($metaKey)) {
			$errors['meta_key'] = __('Meta key is required', 'blockera-site-toolkit');
		}
		if (empty($productId)) {
			$errors['product_id'] = __('Product ID is required', 'blockera-site-toolkit');
		}
		if (empty($version)) {
			$errors['product_version'] = __('Product version is required', 'blockera-site-toolkit'); 
		}
		if (empty($file)) {
			$errors['product_file'] = __('Product file is required', 'blockera-site-toolkit');
		}
		if (empty($filename)) {
			$errors['filename'] = __('Filename is required', 'blockera-site-toolkit');
		}

		// Handle file upload to WooCommerce uploads directory.
		if (! empty($file['tmp_name'])) {
			$upload_dir          = wp_upload_dir();
			$woocommerce_uploads = $upload_dir['basedir'] . '/woocommerce_uploads';

			// Create woocommerce_uploads directory if it doesn't exist.
			if (! file_exists($woocommerce_uploads)) {
				wp_mkdir_p($woocommerce_uploads);
			}

			// Create year/month directories.
			$year      = gmdate('Y');
			$month     = gmdate('m');
			$year_dir  = $woocommerce_uploads . '/' . $year;
			$month_dir = $year_dir . '/' . $month;

			// Create directories if they don't exist.
			if (! file_exists($year_dir)) {
				wp_mkdir_p($year_dir);
			}
			if (! file_exists($month_dir)) {
				wp_mkdir_p($month_dir);
			}

			// Generate unique filename.
			$unique_filename = wp_unique_filename($month_dir, $filename);
			$upload_path     = $month_dir . '/' . $unique_filename;

			// Move uploaded file.
			if (! move_uploaded_file($file['tmp_name'], $upload_path)) {
				$errors['upload'] = __('Failed to upload file', 'blockera-site-toolkit');
			}

			// Update file path to be relative to uploads directory.
			$file = $upload_dir['baseurl'] . '/woocommerce_uploads/' . $year . '/' . $month . '/' . $unique_filename;
		}

		// Validate version format (e.g. 1.0.0 or 1.0.0-beta.1).
		if (! empty($version) && ! preg_match('/^(\d+\.)?(\d+\.)?(\*|\d+)(-[a-zA-Z0-9]+(\.[0-9]+)?)?$/', $version)) {
			$errors['product_version'] = __('Invalid version format. Must be in semantic versioning format (e.g. 1.0.0 or 1.0.0-beta.1)', 'blockera-site-toolkit');
		}

		if (! empty($errors)) {
			return new \WP_REST_Response(
                [
					'code' => 400,
					'success' => false,
					'errors' => $errors,
				],
                400
            );
		}

		$product = wc_get_product($productId);

		if (! $product) {
			return new \WP_REST_Response(
                [
					'code' => 404,
					'success' => false,
					'errors' => [
						'product_id' => __('Product not found', 'blockera-site-toolkit'),
					],
				],
                404
            );
		}

		$previousDownloadableFiles = get_post_meta($productId, 'product_downloadable_files', true);

		$product->update_meta_data('product_version', $request->get_param('product_version'));
		$product->update_meta_data(
            'product_downloadable_files',
            blockera_get_array_deep_merge(
                $previousDownloadableFiles,
                [
					$metaKey => compact('file'),
				]
            )
        );
		$product->save();

		$this->save($productId, get_post($productId), true);

		return new \WP_REST_Response(
            [
				'code' => 200,
				'success' => true,
			],
            200
        );
	}

    /**
     * Save the product.
     *
     * @param int      $postId The post id.
     * @param \WP_Post $post The post object.
     *
     * @return void
     */
    public function save( int $postId, \WP_Post $post, $update): void {
        // Prevent autosave and revision handling.
        if (( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) || wp_is_post_revision($postId)) {
            return;
        }

        // Check if it's a variable product.
        $product = wc_get_product($postId);
        if (! $product || ! $product->is_type('variable')) {
            return;
        }

        $version = get_post_meta($postId, 'product_version', true);

        // Upload general file to remote server.
        $this->uploadGeneralFileToRemoteServer($postId, $version);

        // Get all variations.
        $variations = $product->get_children();

        array_map(
            function ( $variationId) use ( $postId, $version) {
				$this->savingVariation($variationId, $postId, $version);
			},
            $variations
        );
    }

    /**
     * Upload general file to remote server.
     *
     * @param int $postId The post id.
     *
     * @return void
     */
    protected function uploadGeneralFileToRemoteServer( int $postId, string $version = ''): void {
        $product = wc_get_product($postId);
        if (! $product) {
            return;
        }

        $files = $product->get_meta('product_downloadable_files');

        if (empty($files)) {
            return;
        }

        foreach ($files as $key => $fileData) {

			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Existing API property name.
            $result = $this->uploadService->uploadFile($fileData, compact('postId', 'version'));

			if (empty($result['status'])) {
				continue;
			}

			if (! isset($result['data']['download_token'])) {
				continue;
			}

			if ($result['data']['download_token'] === $fileData['hash']) {
				continue;
			}

			$files[ $key ]['hash'] = $result['data']['download_token'];
			update_post_meta($postId, 'product_downloadable_files', $files);
        }
    }

	/**
     * Saving variation.
     *
     * @param int    $variationId The variation id.
     * @param int    $postId The post id.
     * @param string $version The version.
     *
     * @return void
     */
    protected function savingVariation( int $variationId, int $postId, string $version = ''): void {
        $variation = wc_get_product($variationId);
        if (! $variation) {
            return;
        }

        // Get variation object.
        $variation = wc_get_product($variationId);
        if (! $variation) {
            return;
        }

        array_map(
            function ( $download) use ( $variationId, $postId, $version) {
				$this->uploadFileOfVariationDownloads($download, compact('variationId', 'postId', 'version'));
			},
            $variation->get_data()['downloads'] ?? []
        );
    }

    /**
     * Upload file to remote server from variation downloads.
     *
     * @param \WC_Product_Download $download The download object.
     * @param array                $args {
     *                    Array of arguments.
     *     @type int    $variationId The variation id.
     *     @type int    $postId     The post id.
     *     @type string $version    The version.
     * }
     *
     * @return void
     */
    protected function uploadFileOfVariationDownloads( \WC_Product_Download $download, array $args): void {
		$variationId = $args['variationId'] ?? null;
		$postId      = $args['postId'] ?? null;
		$version     = $args['version'] ?? '';
        $fileUrl     = $download->get_file();

        // Skip if file is disabled.
        if (! $download->get_enabled()) {
            return;
        }

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Existing API property name.
		$this->uploadService->uploadFile(
            [
				'file' => $fileUrl,
				'hash' => $download->get_id(),
			],
            compact('variationId', 'postId', 'version')
        );
    }
}
