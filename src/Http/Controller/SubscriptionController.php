<?php

namespace BlockeraAI\SiteToolkit\Http\Controller;

use BlockeraAI\SiteToolkit\Repositories\AccessTokenRepository;

class SubscriptionController
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
     * @return bool true on success, false on otherwise!
     */
    public function permission(\WP_REST_Request $request): bool
    {
        if (!wp_verify_nonce($request->get_param('_wpnonce'), 'wp_rest')) {
            return false;
        }

        if ('cancel' === $request->get_param('action')) {
            wp_redirect($request->get_param('redirect_uri'));
            exit;
        }

        $parsedURL = parse_url($request->get_header('referer'));

        if (home_url() === $parsedURL['scheme'] . '://' . $parsedURL['host']) {
            return true;
        }

        $accessTokenRepo = new AccessTokenRepository();

        if ($accessTokenRepo->isAccessTokenRevoked($request->get_param('access_token'))) {
            return false;
        }

        return true;
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
            return new \WP_REST_Response([
                'code' => '400',
                'success' => false,
                'errors' => $this->errors,
            ], 400);
        }

        global $wpdb;

        // Get the current logged in user.
        $user = wp_get_current_user();

        // Insert client data into the database.
        $result = $wpdb->insert($wpdb->prefix . 'auth_clients', [
            'name' => $user->user_login,
            'domain' => $request->get_param('domain'),
            'client_id' => $request->get_param('client_id'),
            'grant_types' => $request->get_param('grant_types'),
            'redirect_uri' => $request->get_param('redirect_uri'),
            'client_secret' => $request->get_param('client_secret'),
        ]);

        return new \WP_REST_Response([
            'code' => 200,
            'success' => true,
            'data' => $result,
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
            'domain' => 'Domain URL',
            'client_id' => 'Client ID',
            'grant_types' => 'Grant Types',
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
        if (!empty($params['client_id'])) {
            global $wpdb;

            $record = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}auth_clients WHERE client_id = %s", $params['client_id']));

            if ($record) {
                $this->errors[] = __('Current client id already registered!', 'blockera-site-toolkit');
            }
        }

        // Validate domain.
        if (!empty($params['domain']) && !wp_http_validate_url($params['domain'])) {
            $this->errors[] = __('Invalid domain URL.', 'blockera-site-toolkit');
        }

        // Validate redirect URI.
        if (!empty($params['redirect_uri']) && !wp_http_validate_url($params['redirect_uri'])) {
            $this->errors[] = __('Invalid redirect URI.', 'blockera-site-toolkit');
        }

        return empty($this->errors);
    }
}
