<?php

namespace BlockeraAI\SiteToolkit\Http\Controller;

use Blockera\Bootstrap\Application;

class FileController {

	/**
     * Array to store error messages during license validation and management.
     *
     * @var array
     */
    protected array $errors = [];

    /**
     * The application instance.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * Constructor for the LicenseManagerController class.
     *
     * @param Application $app The application instance.
     */
    public function __construct( Application $app) {
        $this->app = $app;
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
        if (str_starts_with( (string) $request->get_header('referer'), home_url())) {
            if (! wp_verify_nonce($request->get_header('X-Blockera-Nonce'), 'blockera-site-toolkit')) {
                return false;
            }

            return true;
        }

        $auth = $request->get_header('Authorization');

        return ! empty($auth) && str_starts_with( (string) $auth, 'Bearer ');
    }

	/**
     * Download the file by token.
     *
     * @param \WP_REST_Request $request The request object.
     *
     * @return \WP_REST_Response The response object.
     */
    public function download( \WP_REST_Request $request): \WP_REST_Response {
        if (empty($request->get_param('token'))) {
            $this->errors['invalid_token'] = __('Token field is required!', 'blockera-site-toolkit');
        }

		if (empty($request->get_param('name'))) {
			$this->errors['invalid_name'] = __('Name field is required!', 'blockera-site-toolkit');
		}

        $userCredentials = bsaGetUserAccessToken('', null, false);

        if (empty($userCredentials) || empty($userCredentials['access_token'])) {
            $this->errors['invalid_authorization'] = __('Authorization field is required!', 'blockera-site-toolkit');
        }

        if (! empty($this->errors)) {
            return new \WP_REST_Response(
                [
					'code' => 400,
					'success' => false,
					'errors' => $this->errors,
				],
                400
            );
        }

        $response = wp_remote_get(
            bsaGetConfig('BSA_API_BASE_URL') . '/files/v1/download/origin/' . $request->get_param('token'),
            [
                'headers' => [
                    'Authorization' => sprintf('Bearer %s', $userCredentials['access_token']),
                ],
                'timeout' => 30,
                'redirection' => 5,
                'httpversion' => '1.1',
                'sslverify' => false,
				'body' => [
					'name' => $request->get_param('name'),
				],
            ]
        );

        if (is_wp_error($response)) {
            return new \WP_REST_Response(
                [
					'code' => 500,
					'success' => false,
					'errors' => [
						'download_error' => $response->get_error_message(),
					],
				],
                500
            );
        }

		$body = json_decode(wp_remote_retrieve_body($response), true);

		if (empty($body['success'])) {
			return new \WP_REST_Response(
                [
					'code' => 400,
					'success' => false,
					'errors' => $body['errors'],
				],
                400
            );
		}

        return new \WP_REST_Response(
            [
				'code' => 200,
				'success' => true,
				'data' => $body['data'],
			],
            200
        );
    }
}
