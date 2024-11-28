<?php

namespace BlockeraAI\SiteToolkit\Http\Controller;

class ClientController
{
    /**
     * The errors array.
     *
     * @var array $errors
     */
    protected array $errors = [];

    /**
     * Check if the user is logged in.
     *
     * @param \WP_REST_Request $request The request object.
     *
     * @return boolean true on success, false on otherwise!
     */
    public function permission(\WP_REST_Request $request): bool
    {
        return is_user_logged_in() && $request->get_header('X-WP-Nonce') === wp_create_nonce('wp_rest');
    }

    /**
     * Register client.
     *
     * @param \WP_REST_Request $request The request object.
     *
     * @return \WP_REST_Response The response object.
     */
    public function register(\WP_REST_Request $request): \WP_REST_Response
    {
        if (!$this->validate($request->get_params())) {
            if (1 === count($this->errors) && array_key_exists('duplicate_client', $this->errors) && isset($this->errors['duplicate_client']['record'])) {
                return new \WP_REST_Response([
                    'code' => 200,
                    'success' => true,
                    'data' => $this->errors['duplicate_client']['record'],
                ], 200);
            }

            return new \WP_REST_Response([
                'code' => '400',
                'success' => false,
                'errors' => $this->errors,
            ], 400);
        }

        global $wpdb;

        // Insert client data into the database.
        $result = $wpdb->insert($wpdb->prefix . 'auth_clients', [
            'domain' => $request->get_param('domain'),
            'user_id' => $request->get_param('user_id'),
            'client_id' => $request->get_param('client_id'),
            'redirect_uri' => $request->get_param('redirect_uri'),
            'client_secret' => $request->get_param('client_secret'),
        ]);

        if ($result === false) {
            return new \WP_REST_Response([
                'code' => 400,
                'success' => false,
                'message' => $wpdb->last_error,
            ], 400);
        }

        return new \WP_REST_Response([
            'code' => 200,
            'success' => true,
            'data' => $request->get_body_params(),
        ], 200);
    }

    /**
     * Validate the request parameters.
     *
     * @param array $params The request parameters.
     *
     * @return boolean true on success, false on otherwise!
     */
    protected function validate(array $params): bool
    {
        $requiredFields = [
            'user_id' => 'User ID',
            'domain' => 'Domain URL',
            'client_id' => 'Client ID',
            'redirect_uri' => 'Redirect URI',
            'client_secret' => 'Client Secret',
        ];

        // Check required fields.
        foreach ($requiredFields as $field => $label) {
            if (empty($params[$field])) {
                $this->errors[] = sprintf(__('%s is required.', 'blockera-site-toolkit'), $label);
            }
        }

        // Check duplicate client.
        if (!empty($params['domain'])) {
            global $wpdb;

            $record = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}auth_clients WHERE domain = %s", $params['domain']));

            if ($record) {
                $this->errors['duplicate_client'] = [
                    'record' => $record,
                    'message' => __('Current client already registered!', 'blockera-site-toolkit'),
                ];
            }
        }

        // Validate domain.
        if (!empty($params['domain']) && !wp_http_validate_url($params['domain'])) {
            $this->errors['invalid_domain'] = __('Invalid domain URL.', 'blockera-site-toolkit');
        }

        // Validate redirect URI.
        if (!empty($params['redirect_uri']) && !wp_http_validate_url($params['redirect_uri'])) {
            $this->errors['invalid_redirect_uri'] = __('Invalid redirect URI.', 'blockera-site-toolkit');
        }

        if (!empty($params['user_id']) && !get_user_by('ID', $params['user_id'])) {
            $this->errors['invalid_user_id'] = __('Invalid resource owner ID.', 'blockera-site-toolkit');
        }

        return empty($this->errors);
    }

    /**
     * Update client authorization code.
     *
     * @param \WP_REST_Request $request The request object.
     *
     * @return \WP_REST_Response The response object.
     */
    public function update(\WP_REST_Request $request): \WP_REST_Response
    {
        if (empty($request->get_param('code'))) {
            return new \WP_REST_Response([
                'code' => 500,
                'success' => false,
                'errors' => [
                    'invalid_code' => __('Invalid code field', 'blockera-site-toolkit'),
                ],
            ], 500);
        }

        if (empty($request->get_param('grant_type'))) {
            return new \WP_REST_Response([
                'code' => 500,
                'success' => false,
                'errors' => [
                    'invalid_grant_type' => __('Invalid grant field.', 'blockera-site-toolkit'),
                ],
            ], 500);
        }

        global $wpdb;

        $result = $wpdb->update(
            $wpdb->prefix . 'auth_clients',
            [
                'auth_code' => $request->get_param('code'),
                'grant_type' => $request->get_param('grant_type'),
            ],
            [
                'client_id' => $request->get_param('client_id'),
            ],
            [
                '%s',
                '%s',
            ],
            [
                '%s',
            ]
        );

        if (false === $result) {
            return new \WP_REST_Response([
                'code' => 500,
                'success' => false,
                'errors' => [
                    'database_error' => __('Failed to update client authorization code.', 'blockera-site-toolkit'),
                ],
            ], 500);
        }

        if (0 === $result) {
            return new \WP_REST_Response([
                'code' => 404,
                'success' => false,
                'errors' => [
                    'not_found' => __('Client not found.', 'blockera-site-toolkit'),
                ],
            ], 404);
        }

        return new \WP_REST_Response([
            'code' => 200,
            'success' => true,
            'data' => $result,
        ], 200);
    }
}
