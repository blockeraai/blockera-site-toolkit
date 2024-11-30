<?php

namespace BlockeraAI\SiteToolkit\Providers;

use BlockeraAI\SiteToolkit\Setup;
use League\OAuth2\Server\CryptKey;
use Blockera\Bootstrap\ServiceProvider;
use Psr\Http\Message\ResponseInterface;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Grant\PasswordGrant;
use BlockeraAI\SiteToolkit\Repositories\UserRepository;
use BlockeraAI\SiteToolkit\Repositories\ScopeRepository;
use BlockeraAI\SiteToolkit\Repositories\ClientRepository;
use BlockeraAI\SiteToolkit\Repositories\AuthCodeRepository;
use BlockeraAI\SiteToolkit\Http\Controller\ClientController;
use BlockeraAI\SiteToolkit\Repositories\AccessTokenRepository;
use BlockeraAI\SiteToolkit\Repositories\RefreshTokenRepository;
use BlockeraAI\SiteToolkit\Http\Controller\LicenseManagerController;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        if ($this->app instanceof Setup) {

            // Configure the OAuth server.
            $privateKey = new CryptKey(BSA_PLUGIN_DIR . 'private.key', null, false);
            $encryptionKey = 'CRNkX6YdtGYbjbpSsz/xjMRrO9wct+flivtmaGEHn0E=';

            $authServer = new AuthorizationServer(
                new ClientRepository(),
                new AccessTokenRepository(),
                new ScopeRepository(),
                $privateKey,
                $encryptionKey
            );

            $authServer->enableGrantType(
                new AuthCodeGrant(
                    new AuthCodeRepository(),
                    new RefreshTokenRepository(),
                    new \DateInterval('PT1M') // Authorization codes expire in 1 minutes.
                ),
                new \DateInterval('P1Y') // Access tokens expire in 1 year.
            );

            $authServer->enableGrantType(
                new PasswordGrant(
                    new UserRepository(),
                    new RefreshTokenRepository()
                ),
                new \DateInterval('PT1H') // Access tokens expire in 1 hour.
            );

            // Set the OAuth server.
            $this->app->setServer($authServer);
        }
    }

    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->app instanceof Setup) {
            // Register REST API routes.
            $this->app->registerRoutes();
        }

        add_filter('http_request_host_is_external', function ($is_external, $host) {
            if (str_ends_with($host, 'localhost') || str_ends_with($host, '127.0.0.1') || str_ends_with($host, '.test')) {
                return true;
            }

            return $is_external;
        }, 10, 2);

        add_action('blockera-site-toolkit/rest/post/authorize', [$this, 'doUpdateClient'], 10, 3);

        // Doing register client request if user is logged in.
        is_user_logged_in() && $this->doRegisterClientRequest();

        // Add endpoint for the Blockera License Manager page.
        add_rewrite_endpoint('license-manager', EP_ROOT | EP_PAGES);

        // Add Blockera License Manager page to woocommerce my account menu.
        add_filter('woocommerce_account_menu_items', [$this, 'addLicenseManagerPage']);

        // Add content for the Blockera OAuth page.
        add_action('woocommerce_account_license-manager_endpoint', [$this, 'renderLicenseManagerView']);

        // Flush rewrite rules to ensure new endpoints are registered.
        flush_rewrite_rules();
    }

    public function doUpdateClient(ResponseInterface $responseInterface, string $grantType, string $clientId): void
    {
        parse_str(parse_url($responseInterface->getHeader('Location')[0])['query'], $params);

        $request = new \WP_REST_Request('POST', 'auth/v1/client/update');

        $params['client_id'] = $clientId;
        $params['grant_type'] = $grantType;

        $request->set_body_params($params);

        $response = (new ClientController())->update($request);

        $this->validateResponse($response);
    }

    /**
     * Get the register client request parameters.
     *
     * @return array
     */
    protected function getRegisterClientParams(): array
    {
        parse_str(parse_url($_SERVER['HTTP_REFERER'])['query'], $params);

        $redirect_to = $params['redirect_to'];
        $parsed_redirect_to = parse_url(home_url($redirect_to));

        if (empty($parsed_redirect_to['query'])) {
            return [];
        }

        parse_str($parsed_redirect_to['query'], $params);

        $parsed_redirect_uri = parse_url($params['redirect_uri']);
        $domain = "{$parsed_redirect_uri['scheme']}://{$parsed_redirect_uri['host']}";

        // Sanitize and get form data.
        $redirect_uri = esc_url_raw($params['redirect_uri']);
        $grant_types = 'authorization_code';
        // Get the current logged in user identifier.
        $user_id = wp_get_current_user()->ID;
        // Generate client_id and client_secret.
        $client_id = wp_generate_uuid4();
        $client_secret = wp_generate_password(32, false);

        return compact('user_id', 'client_id', 'client_secret', 'domain', 'redirect_uri', 'redirect_to');
    }

    /**
     * Set the blockera authentication status in database.
     *
     * @return void
     */
    public function doRegisterClientRequest(): void
    {
        if (!isset($_SERVER['HTTP_REFERER']) || false === strpos($_SERVER['HTTP_REFERER'], urlencode('authorize/?'))) {
            return;
        }

        $params = $this->getRegisterClientParams();

        if (empty($params)) {
            return;
        }

        $request = new \WP_REST_Request('POST', '/auth/v1/client/register');

        $request->set_body_params($params);

        $request->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));

        $registrationClientResponse = rest_do_request($request);

        // Validate the request and handle any errors.
        $this->validateResponse($registrationClientResponse);

        $response = $registrationClientResponse->get_data();

        $authRequest = new \WP_REST_Request('POST', '/auth/v1/authorize');

        $client_id = $response['data']->client_id;
        $client_secret = $response['data']->client_secret;

        $authRequest->set_query_params(array_merge($_GET, compact('client_id')));

        $authRequest->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));

        $authResponse = rest_do_request($authRequest);

        // Validate the request and handle any errors.
        $this->validateResponse($authResponse);

        $data = $authResponse->get_data();

        if (302 !== $data->getStatusCode()) {
            return;
        }

        $redirect_uri = $data->getHeader('Location')[0];

        // Redirect to the client page.
        wp_redirect($redirect_uri . "&client_id=$client_id&client_secret=$client_secret&redirect_to=" . urlencode(home_url($params['redirect_to'])), 302);

        // Stop further WordPress execution for this request.
        exit;
    }

    /**
     * Validates a REST API response and handles any errors by displaying them and exiting.
     *
     * @param \WP_REST_Response $response The response object to validate.
     * @return void
     */
    protected function validateResponse(\WP_REST_Response $response): void
    {
        if (is_wp_error($response)) {
            wp_die($response->get_error_message());
            exit;
        }

        $data = $response->get_data();

        if (400 === $response->get_status()) {
            foreach ($data['errors'] as $error) {
                wp_die($error);
            }

            exit;
        }

        if (200 !== $response->get_status()) {
            wp_die($data['message']);

            exit;
        }
    }

    /**
     * Add the License Manager menu item to the woocommerce my account menu items.
     *
     * @param array $menu_items The menu items array.
     * 
     * @return array Updated menu items array.
     */
    public function addLicenseManagerPage(array $menu_items): array
    {
        $menu_items['license-manager'] = __('License Manager', 'blockera-site-toolkit');

        return $menu_items;
    }

    /**
     * Render the license manager view template.
     *
     * @return void
     */
    public function renderLicenseManagerView(): void
    {
        $this->app->make(LicenseManagerController::class)->render();
    }
}
