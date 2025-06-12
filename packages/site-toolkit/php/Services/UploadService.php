<?php

namespace BlockeraAI\SiteToolkit\Services;

class UploadService
{
	/**
	 * The boundary.
	 *
	 * @var string
	 */
	protected string $boundary;

    /**
     * Upload file to remote server.
     *
     * @param array $fileData The file data.
     * @param array $args {
     *     Array of arguments.
     *     @type int    $postId The post id.
     *     @type string $version The version.
     * }
     * @return array The result of the upload process.
     */
    public function uploadFile(array $fileData, array $args): array
    {
        $file = str_replace(get_site_url() . '/', ABSPATH, $fileData['file']);

        // Throw error if file is empty or not exists.
        if (empty($file) || !file_exists($file)) {
            return [
				'status' => false,
				'message' => __('File not found.', 'blockera-site-toolkit'),
			];
        }

		// Get the request payload.
		$payload = $this->getPayload(array_merge($fileData, compact('file')), $args);

        $response = wp_remote_post(
            bsaGetConfig('BSA_API_BASE_URL') . '/files/v1/upload',
            [
                'timeout' => 30,
                'sslverify' => false,
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'multipart/form-data; boundary=' . $this->getBoundary()
                ],
                'body' => $payload
            ]
        );

        $status = wp_remote_retrieve_response_code($response);

        // Occurs when the file already exists.
        if (200 === $status) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
			
            // Delete the file from the WordPress uploads directory.
            $this->deleteFile($fileData['file']);

            return [
				'status' => true,
				'data' => $body['data'],
				'message' => __('File uploaded successfully.', 'blockera-site-toolkit'),
			];
        }

        // Skip if request failed.
        if (is_wp_error($response) || $status !== 201) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            wp_die(implode(', ', iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveArrayIterator($body['errors'])))));
        }

		return [
			'status' => false,
			'message' => __('File upload failed.', 'blockera-site-toolkit'),
		];
    }

    /**
     * Get the payload for the upload file.
	 * 
	 * @param array $fileData {
	 *     Array of file data.
	 *     @type string $file The file.
	 *     @type string $hash The hash.
	 * }
	 * @param array $args {
	 *     Array of arguments.
	 *     @type int    $postId The post id.
	 *     @type string $version The version.
	 *     @type int    $variationId The variation id.
	 * }
     *
	 * @return string The payload.
     */
    public function getPayload(array $fileData, array $args): string
    {
		// Upload file to remote server
        $boundary = $this->getBoundary();
        $payload = '';

        // Add text fields
        $payload .= '--' . $boundary . "\r\n";
        $payload .= 'Content-Disposition: form-data; name="token"' . "\r\n\r\n";
        $payload .= $fileData['hash'] . "\r\n";

		// Add name.
        $payload .= '--' . $boundary . "\r\n";
        $payload .= 'Content-Disposition: form-data; name="name"' . "\r\n\r\n";
        $payload .= bsaGetFileName($fileData['file']) . "\r\n";

		// Add variation id if exists.
        if (!empty($args['variationId'])) {
			$payload .= '--' . $boundary . "\r\n";
			$payload .= 'Content-Disposition: form-data; name="variation_id"' . "\r\n\r\n";
			$payload .= $args['variationId'] . "\r\n";
        }

		// Add product id.
        $payload .= '--' . $boundary . "\r\n";
        $payload .= 'Content-Disposition: form-data; name="product_id"' . "\r\n\r\n";
        $payload .= $args['postId'] . "\r\n";

		// Add version.
        $payload .= '--' . $boundary . "\r\n";
        $payload .= 'Content-Disposition: form-data; name="version"' . "\r\n\r\n";
        $payload .= (empty($fileData['version']) ? $args['version'] ?? '' : $fileData['version']) . "\r\n";

        // Add file name and content.
        $payload .= '--' . $boundary . "\r\n";
        $payload .= 'Content-Disposition: form-data; name="file"; filename="' . basename($fileData['file']) . '"' . "\r\n";
        $payload .= 'Content-Type: application/octet-stream' . "\r\n\r\n";
        $payload .= file_get_contents($fileData['file']) . "\r\n";

		// Add boundary.
        $payload .= '--' . $boundary . '--';

		return $payload;
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

	/**
	 * Get the boundary.
	 *
	 * @return string The boundary.
	 */
	public function getBoundary(): string
	{
		if (empty($this->boundary)) {
			$this->boundary = wp_generate_password(24);
		}

		return $this->boundary;
	}
}
