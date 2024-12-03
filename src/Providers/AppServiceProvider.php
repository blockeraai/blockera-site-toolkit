<?php

namespace BlockeraAI\SiteToolkit\Providers;

use BlockeraAI\SiteToolkit\Setup;
use League\OAuth2\Server\CryptKey;
use Blockera\Bootstrap\ServiceProvider;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Grant\PasswordGrant;
use BlockeraAI\SiteToolkit\Repositories\UserRepository;
use BlockeraAI\SiteToolkit\Repositories\ScopeRepository;
use BlockeraAI\SiteToolkit\Repositories\ClientRepository;
use BlockeraAI\SiteToolkit\Repositories\AuthCodeRepository;
use BlockeraAI\SiteToolkit\Http\Middlewares\RefererMiddleware;
use BlockeraAI\SiteToolkit\Repositories\AccessTokenRepository;
use BlockeraAI\SiteToolkit\Http\Middlewares\MiddlewarePipeline;
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

            $this->app->singleton(MiddlewarePipeline::class);
            $this->app->singleton(RefererMiddleware::class);
        }
    }

    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot(): void
    {
        if (!$this->app instanceof Setup) {
            return;
        }

        // Register REST API routes.
        $this->app->registerRoutes();

        add_filter('http_request_host_is_external', function ($is_external, $host) {
            if (str_ends_with($host, 'localhost') || str_ends_with($host, '127.0.0.1') || str_ends_with($host, '.test')) {
                return true;
            }

            return $is_external;
        }, 10, 2);

        add_action('blockera-site-toolkit/rest/post/authorize', 'bsaDoUpdateClient', 10, 3);

        // Doing register client request if user is logged in.
        is_user_logged_in() && bsaDoRegisterClientRequest($this->app);

        // Add endpoint for the Blockera License Manager page.
        add_rewrite_endpoint('license-manager', EP_ROOT | EP_PAGES);
        add_rewrite_endpoint('license-manager-clients', EP_ROOT | EP_PAGES);

        // Add Blockera License Manager page to woocommerce my account menu.
        add_filter('woocommerce_account_menu_items', [$this, 'addLicenseManagerPage']);

        // Add content for the Blockera OAuth page.
        add_action('woocommerce_account_license-manager_endpoint', [$this->app->make(LicenseManagerController::class), 'render']);
        add_action('woocommerce_account_license-manager-clients_endpoint', [$this->app->make(LicenseManagerController::class), 'renderClients']);
        // Flush rewrite rules to ensure new endpoints are registered.
        flush_rewrite_rules();
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
        $menu_items['license-manager-clients'] = __('Your activated domains', 'blockera-site-toolkit');

        return $menu_items;
    }
}
