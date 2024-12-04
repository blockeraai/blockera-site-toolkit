<?php

namespace BlockeraAI\SiteToolkit\Http\Controller;

use Blockera\Utils\View;
use Blockera\Utils\Utils;
use BlockeraAI\SiteToolkit\Repositories\UserRepository;
use BlockeraAI\SiteToolkit\Repositories\LicenseRepository;
use BlockeraAI\SiteToolkit\Repositories\AccessTokenRepository;

class LicenseManagerController
{
    /**
     * Array to store error messages during license validation and management.
     *
     * @var array
     */
    protected array $errors = [];

    protected LicenseRepository $licenseRepository;

    public function __construct(LicenseRepository $licenseRepository)
    {
        $this->licenseRepository = $licenseRepository;
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
     * Render the license manager view template.
     *
     * @return void
     */
    public function render(): void
    {
        // Compatible with the yith-woocommerce-subscription-premium plugin.
        if (!function_exists('ywsbs_get_status') || !function_exists('YWSBS_Subscription_Helper')) {
            return;
        }

        /**
         * Show the consent template for the current user's subscriptions.
         * The template will display differently based on the number of active subscriptions:
         * - If user has one subscription: Shows single subscription template.
         * - If user has multiple subscriptions: Shows multiple choice template.
         * - If user has no subscriptions: Shows empty state template.
         */

        $root_path = BSA_PLUGIN_DIR . '/src/Views/';
        $subscription_statuses = ywsbs_get_status();

        $subscriptions = array_filter(
            YWSBS_Subscription_Helper()->get_subscriptions_by_user(
                get_current_user_id(),
                []
            ),
            [$this, 'isActiveSubscription']
        );

        $whoIs = Utils::extractDomainName($_GET['redirect_uri'] ?? '');
        $url = Utils::extractDomainName($_GET['redirect_uri'] ?? '', true);

        if (empty($_GET['redirect_uri']) && count($subscriptions) && empty($_GET['registered-client']) && empty($whoIs)) {
            View::load('license-manager.register-client', compact('subscriptions', 'root_path'), ['root-path' => $root_path]);
        } elseif (empty($_GET['redirect_uri']) && !count($subscriptions)) {
            View::load('license-manager.404', [], ['root-path' => $root_path]);
        } elseif (1 === count($subscriptions) && !empty($whoIs)) {
            $subscription_post = $subscriptions[0];
            $subscription_id = is_numeric($subscription_post) ? $subscription_post : $subscription_post->ID;

            View::load('license-manager.single-consent-form', compact('url', 'whoIs', 'subscription_id', 'subscription_statuses'), ['root-path' => $root_path]);
        } elseif (1 < count($subscriptions) && !empty($whoIs)) {
            View::load('license-manager.archive-consent-form', compact('url', 'whoIs', 'subscriptions', 'subscription_statuses'), ['root-path' => $root_path]);
        }
    }

    public function renderClients(): void
    {
        // Compatible with the yith-woocommerce-subscription-premium plugin.
        if (!function_exists('ywsbs_get_status') || !function_exists('YWSBS_Subscription_Helper')) {
            return;
        }

        $root_path = BSA_PLUGIN_DIR . '/src/Views/';
        $subscription_statuses = ywsbs_get_status();

        $user = new UserRepository();

        $clients = $user->getClients(get_current_user_id());

        View::load('license-manager.registered-clients', compact('clients', 'subscription_statuses'), ['root-path' => $root_path]);
    }

    /**
     * Filter subscription by checking if subscription status is active.
     *
     * @param \WP_Post $subscription_post
     * @return boolean
     */
    protected function isActiveSubscription(\WP_Post $subscription_post): bool
    {
        $subscription_statuses = ywsbs_get_status();
        $subscription_id = is_numeric($subscription_post) ? $subscription_post : $subscription_post->ID;
        $subscription = ywsbs_get_subscription($subscription_id);

        return !empty($subscription_statuses[$subscription->get_status()]) && 'active' === $subscription_statuses[$subscription->get_status()];
    }

    /**
     * Create a new license for the current client.
     *
     * @param \WP_REST_Request $request The request object.
     * @param LicenseRepository $licenseRepository The license repository object.
     *
     * @return \WP_REST_Response The response object.
     */
    public function create(\WP_REST_Request $request): \WP_REST_Response
    {
        $this->validate($request->get_params());

        if (!empty($this->errors)) {
            return new \WP_REST_Response([
                'code' => 400,
                'success' => false,
                'errors' => $this->errors,
            ], 400);
        }

        try {
            $result = $this->licenseRepository->create([
                'client_id' => $request->get_param('client_id'),
                'subscription_id' => (int)$request->get_param('subscription_id'),
            ]);
        } catch (\Exception $e) {
            return new \WP_REST_Response([
                'code' => 500,
                'success' => false,
                'errors' => [
                    'database_error' => __('Client already exists!', 'blockera-site-toolkit'),
                ],
            ], 500);
        }

        return new \WP_REST_Response([
            'code' => 200,
            'success' => true,
            'data' => $result,
        ], 200);
    }

    protected function validate(array $params): void
    {
        $requiredParams = [
            'scopes' => __('Scopes field is required!', 'blockera-site-toolkit'),
            'client_id' => __('Client ID field is required!', 'blockera-site-toolkit'),
            'subscription_id' => __('Subscription field is required!', 'blockera-site-toolkit'),
        ];

        foreach ($requiredParams as $key => $errorMessage) {
            if (empty($params[$key])) {
                $this->errors[$key] = $errorMessage;
            }
        }
    }
}
