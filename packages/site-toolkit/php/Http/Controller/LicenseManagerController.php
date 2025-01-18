<?php

namespace BlockeraAI\SiteToolkit\Http\Controller;

use Blockera\Bootstrap\Application;
use BlockeraAI\SiteToolkit\Guard\SecureDownloadManager;
use BlockeraAI\SiteToolkit\Repositories\ClientRepository;
use BlockeraAI\SiteToolkit\Repositories\SubscriptionRepository;

class LicenseManagerController
{
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
    public function __construct(Application $app)
    {
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
    public function permission(\WP_REST_Request $request): bool
    {
        if (str_starts_with($request->get_header('referer'), home_url())) {
            if (!wp_verify_nonce($request->get_header('X-Blockera-Nonce'), 'blockera-site-toolkit')) {
                return false;
            }

            return true;
        }

        $auth = $request->get_header('Authorization');

        return !empty($auth) && str_starts_with($auth, 'Bearer ');
    }

    /**
     * Process a license renewal request.
     * Validates the action and license ID parameters, then attempts to renew the subscription.
     * Returns error response if validation fails or renewal process errors out.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function renew(\WP_REST_Request $request): \WP_REST_Response
    {
        if ('renew-process' !== $request->get_param('action')) {
            $this->errors['invalid_action'] = __('The action parameter is invalid or missing.', 'blockera');
        }
        if (empty($request->get_param('license_id'))) {
            $this->errors['required_license_id'] = __('The license identifier param is required!', 'blockera');
        }

        try {
            $checkoutUrl = bsaRenewalSubscription($request->get_param('license_id'));
        } catch (\Exception $error) {
            $this->errors['failed_renew_process'] = $error->getMessage();

            return new \WP_REST_Response([
                'success' => false,
                'errors' => $this->errors,
            ], 500);
        }

        return new \WP_REST_Response([
            'success' => true,
            'data' => [
                'checkout_url' => $checkoutUrl,
            ],
        ]);
    }

    /**
     * Process a license upgrade request.
     * Validates the action and license ID parameters, then attempts to upgrade the subscription.
     * Returns error response if validation fails or upgrade process errors out.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function upgrade(\WP_REST_Request $request): \WP_REST_Response
    {
        if ('upgrade-process' !== $request->get_param('action')) {
            $this->errors['invalid_action'] = __('The action parameter is invalid or missing.', 'blockera');
        }
        if (empty($request->get_param('license_id'))) {
            $this->errors['required_license_id'] = __('The license identifier param is required!', 'blockera');
        }

        try {
            $checkoutUrl = bsaUpgradingSubscription($request->get_param('license_id'));
        } catch (\Exception $error) {
            $this->errors['failed_upgrade_process'] = $error->getMessage();

            return new \WP_REST_Response([
                'success' => false,
                'errors' => $this->errors,
            ], 500);
        }

        return new \WP_REST_Response([
            'success' => true,
            'data' => [
                'checkout_url' => $checkoutUrl,
            ],
        ]);
    }

    /**
     * Get licenses information for a client.
     * Validates the client ID and makes an API request to retrieve license data.
     * Returns error response if validation fails or API request errors out.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function index(\WP_REST_Request $request): \WP_REST_Response
    {
        if (empty($request->get_param('client_id'))) {
            $this->errors['invalid_client_id'] = __('Client ID field is required!', 'blockera-site-toolkit');
        }

        if (!wp_is_uuid($request->get_param('client_id'))) {
            $this->errors['invalid_client_id'] = __('Invalid Client ID format.', 'blockera-site-toolkit');
        }

        if (!empty($this->errors)) {
            return new \WP_REST_Response([
                'code' => 400,
                'success' => false,
                'errors' => $this->errors,
            ], 400);
        }

        try {
            $response = wp_remote_get(
                bsaGetConfig('BSA_API_BASE_URL') . '/clients-manager/v1/clients',
                [
                    'timeout' => 30,
                    'redirection' => 5,
                    'httpversion' => '1.1',
                    'sslverify' => false,
                    'headers' => [
                        'Authorization' => $request->get_header('Authorization'),
                    ],
                    'body' => [
                        'domain' => $request->get_param('domain'),
                    ],
                ]
            );

            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);

            if (empty($body['success']) || !isset($body['data']['licenses']) || empty($body['data']['licenses'])) {
                return new \WP_REST_Response([
                    'code' => 400,
                    'success' => false,
                    'errors' => $body['data']['errors'],
                ], 400);
            }

            $licenses = array_map(function (array $license) {
                $subscriptionRepository = new SubscriptionRepository();
                $subscriptionInfo = $subscriptionRepository->getSubscriptionInfo($license['license_id']);

                return array_merge([
                    'domain' => $license['domain'],
                    'licenseKey' => $license['license'],
                ], $subscriptionInfo);
            }, $body['data']['licenses']);
        } catch (\Exception $e) {
            return new \WP_REST_Response([
                'code' => 500,
                'success' => false,
                'errors' => [
                    'database_error' => $e->getMessage(),
                ],
            ], 500);
        }

        $body['data']['licenses'] = $licenses;

        return new \WP_REST_Response([
            'code' => 200,
            'success' => true,
            'data' => $body['data'],
        ], 200);
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
            $userCredentials = bsaGetUserAccessToken();

            if (empty($userCredentials)) {
                throw new \Exception('User credentials not found!');
            }

            if (empty($request->get_param('client_id'))) {
                $client = $this->tryRegisterClient($request->get_params(), $userCredentials['token_type'] . ' ' . $userCredentials['access_token']);

                if (empty($client)) {
                    throw new \Exception('Unable to register your client. Please check your connection and try again. If the issue persists, contact support.');
                }

                $request->set_param('client_id', $client['client_id']);
            }

            $response = wp_remote_post(
                bsaGetConfig('BSA_API_BASE_URL') . '/license-manager/v1/licenses',
                [
                    'timeout' => 30,
                    'redirection' => 5,
                    'httpversion' => '1.1',
                    'sslverify' => false,
                    'headers' => [
                        'Authorization' => $userCredentials['token_type'] . ' ' . $userCredentials['access_token'],
                    ],
                    'body' => $request->get_params(),
                ]
            );

            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }
        } catch (\Exception $e) {
            return new \WP_REST_Response([
                'code' => 500,
                'success' => false,
                'errors' => [
                    'exception' => $e->getMessage(),
                ],
            ], 500);
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($body['success']) {
            return new \WP_REST_Response([
                'code' => 200,
                'success' => true,
                'data' => $body['data'],
            ], 200);
        }

        return new \WP_REST_Response([
            'code' => 400,
            'success' => false,
            'errors' => $body['data']['errors'],
        ], 400);
    }

    /**
     * Delete the license by client ID.
     *
     * @param \WP_REST_Request $request The request object.
     *
     * @return \WP_REST_Response The response object.
     */
    public function delete(\WP_REST_Request $request): \WP_REST_Response
    {
        if (empty($request->get_param('domain_id')) || !filter_var($request->get_param('domain_id'), FILTER_VALIDATE_DOMAIN)) {
            return new \WP_REST_Response([
                'code' => 400,
                'success' => false,
                'errors' => [
                    'invalid_domain_id' => __('Invalid Domain ID.', 'blockera-site-toolkit'),
                ],
            ], 400);
        }

        try {
            $userCredentials = bsaGetUserAccessToken();

            if (empty($userCredentials)) {
                throw new \Exception('User credentials not found!');
            }

            $response = wp_remote_request(
                bsaGetConfig('BSA_API_BASE_URL') . '/license-manager/v1/licenses/' . $request->get_param('domain_id'),
                [
                    'method' => 'DELETE',
                    'timeout' => 30,
                    'redirection' => 5,
                    'httpversion' => '1.1',
                    'sslverify' => false,
                    'headers' => [
                        'Accept' => 'application/json',
                        'Authorization' => $userCredentials['token_type'] . ' ' . $userCredentials['access_token'],
                    ],
                    'body' => [
                        'domain' => $request->get_param('domain'),
                    ],
                ]
            );

            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }
        } catch (\Exception $e) {
            return new \WP_REST_Response([
                'code' => 400,
                'success' => false,
                'errors' => [
                    'delete_error' => $e->getMessage(),
                ],
            ], 400);
        }

        $responseCode = wp_remote_retrieve_response_code($response);

        return new \WP_REST_Response([
            'code' => $responseCode,
            'success' => $responseCode === 200,
        ], $responseCode);
    }

    /**
     * Validate the zip file.
     *
     * @param \WP_REST_Request $request The request object.
     *
     * @return \WP_REST_Response The response object.
     */
    public function validateZipFile(\WP_REST_Request $request): \WP_REST_Response
    {
        if (empty($request->get_param('zip_file'))) {
            $this->errors['invalid_zip_file'] = __('Zip file url field is required!', 'blockera-site-toolkit');
        }

        if (!filter_var($request->get_param('zip_file'), FILTER_VALIDATE_URL)) {
            $this->errors['invalid_zip_file'] = __('Invalid zip file url format.', 'blockera-site-toolkit');
        }

        if (empty($request->get_param('client_id'))) {
            $this->errors['invalid_client_id'] = __('Client ID field is required!', 'blockera-site-toolkit');
        }

        if (!empty($this->errors)) {
            return new \WP_REST_Response([
                'code' => 400,
                'success' => false,
                'errors' => $this->errors,
            ], 400);
        }

        $isValidZipFile = $this->licenseRepository->isValidZipFile($request->get_param('zip_file'));

        if (!$isValidZipFile) {
            return new \WP_REST_Response([
                'code' => 400,
                'success' => false,
                'errors' => [
                    'not_found' => __('Invalid zip file.', 'blockera-site-toolkit'),
                ],
            ], 400);
        }

        return new \WP_REST_Response([
            'code' => 200,
            'success' => true,
        ], 200);
    }

    /**
     * Get the zip file by domain.
     *
     * @param \WP_REST_Request $request The request object.
     *
     * @return \WP_REST_Response The response object.
     */
    public function getZipFile(\WP_REST_Request $request): \WP_REST_Response
    {
        if (empty($request->get_param('client_id'))) {
            $this->errors['invalid_client_id'] = __('Client ID field is required!', 'blockera-site-toolkit');
        }

        if (empty($request->get_param('subscription_id'))) {
            $this->errors['invalid_subscription_id'] = __('Subscription ID field is required!', 'blockera-site-toolkit');
        }

        try {
            $userId = $this->app->make('clientRepository')->getClientBy('client_id', $request->get_param('client_id'))->user_id;

            if (empty($userId)) {
                throw new \Exception('User ID field is required!');
            }

            wp_set_current_user($userId);
            wp_set_auth_cookie($userId);

            $isDownloadable = $this->app->make(
                'subscriptionRepository',
                [
                    'clientRepository' => $this->app->make('clientRepository'),
                    'licenseRepository' => $this->app->make('licenseRepository'),
                ]
            )->isDownloadable($request->get_param('domain'), $userId);

            if (!$isDownloadable) {
                throw new \Exception('You are not allowed to download the zip file.');
            }

            $clientId = $this->app->make('clientRepository')->getClientBy('domain', $request->get_param('domain'))->client_id;

            if (empty($clientId)) {
                throw new \Exception('Client ID field is required!');
            }

            $secureDownloadManager = $this->app->make(SecureDownloadManager::class);

            $downloadLink = $secureDownloadManager->generateDownloadLink(
                $clientId,
                $request->get_param('client_id'),
                $request->get_param('subscription_id')
            );
        } catch (\Exception $e) {
            return new \WP_REST_Response([
                'code' => 500,
                'success' => false,
                'errors' => [
                    'database_error' => [
                        'database_error' => $e->getMessage(),
                    ],
                ],
            ], 500);
        }

        return new \WP_REST_Response([
            'code' => 200,
            'success' => true,
            'data' => compact('downloadLink'),
        ], 200);
    }

    /**
     * Validate the request parameters.
     *
     * @param array $params The request parameters.
     *
     * @return void
     */
    protected function validate(array $params): void
    {
        $requiredParams = [
            'domain' => __('Domain field is required!', 'blockera-site-toolkit'),
            'license_id' => __('License field is required!', 'blockera-site-toolkit'),
        ];

        foreach ($requiredParams as $key => $errorMessage) {
            if (empty($params[$key])) {
                $this->errors[$key] = $errorMessage;
            }
        }
    }

    /**
     * Try to register the client.
     *
     * @param array $params The request parameters.
     * @param string $authorization The authorization header.
     *
     * @return array The client data and authorization response.
     */
    protected function tryRegisterClient(array $params, string $authorization): array
    {
        $clientRepository = new ClientRepository();
        $client = $clientRepository->getBy('domain', $params['domain']);
        $user = wp_get_current_user();

        $redirectUri = admin_url('admin.php/?page=blockera-settings-connect-with-account');

        $requiredParams = empty($client) ? array_merge(bsaGetRegisterClientParams(false), [
            'domain' => $params['domain'],
            'redirect_uri' => $redirectUri,
            'event' => 'registration-client',
            'internal_redirect' => $redirectUri,
        ]) : [
            'response_type' => 'code',
            'domain' => $params['domain'],
            'redirect_uri' => $redirectUri,
            'username' => $user->user_email,
            'event' => 'registration-client',
            'internal_redirect' => $redirectUri,
            'grant_types' => 'authorization_code',
        ];

        if (empty($client)) {
            $client = bsaDoStoreClient($requiredParams, $authorization);

            if (empty($client)) {
                throw new \Exception('Unable to register your client. Please check your connection and try again. If the issue persists, contact support.');
            }
        }

        $requiredParams = [
            'params' => $requiredParams,
            'authorization' => $authorization,
        ];

        $authorizeResponse = bsaDoAuthorization($client['client_id'], $client['client_secret'], $requiredParams);

        if (empty($authorizeResponse['redirect_to_client'])) {
            throw new \Exception('Client authorization failed!');
        }

        $parsedUrl = parse_url($authorizeResponse['redirect_to_client']);
        parse_str($parsedUrl['query'], $queryParams);

        $response = wp_remote_post(
            bsaGetConfig('BSA_API_BASE_URL') . '/auth/v1/access-token',
            [
                'timeout' => 30,
                'redirection' => 5,
                'httpversion' => '1.1',
                'sslverify' => false,
                'headers' => [
                    'Referer' => home_url(),
                    'Authorization' => $authorization,
                ],
                'body' => [
                    'domain' => $params['domain'],
                    'redirect_uri' => $redirectUri,
                    'client_id' => $client['client_id'],
                    'grant_type' => 'authorization_code',
                    'code' => $queryParams['code'] ?? '',
                    'client_secret' => $client['client_secret'],
                ],
            ]
        );

        if (is_wp_error($response)) {
            throw new \Exception('Client Authentication failed.');
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($body['access_token'])) {
            throw new \Exception('Client Authentication failed.');
        }

        return $client;
    }
}
