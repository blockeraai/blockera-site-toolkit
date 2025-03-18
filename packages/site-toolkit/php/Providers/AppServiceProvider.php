<?php

namespace BlockeraAI\SiteToolkit\Providers;

use BlockeraAI\SiteToolkit\Setup;
use Blockera\Bootstrap\ServiceProvider;
use BlockeraAI\SiteToolkit\Meta\Factory as Meta;
use BlockeraAI\SiteToolkit\Guard\SecureDownloadManager;
use BlockeraAI\SiteToolkit\Http\Controller\ProductController;
use BlockeraAI\SiteToolkit\Http\Middlewares\RefererMiddleware;
use BlockeraAI\SiteToolkit\Http\Middlewares\MiddlewarePipeline;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(MiddlewarePipeline::class);
        $this->app->singleton(RefererMiddleware::class);

        // $this->app->singleton(SecureDownloadManager::class, function (Application $app) {
        //     return new SecureDownloadManager($app, new SecureDownloadRepository());
        // });
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

        // Process download zip file request.
        if (!empty($_GET['action']) && 'download' === $_GET['action'] && !empty($_GET['token']) && !empty($_GET['hash'])) {
            $this->app->make(SecureDownloadManager::class)->processDownload($_GET['token'], $_GET['hash']);
        }

        // Register REST API routes.
        $this->app->registerRoutes();

        add_filter('http_request_host_is_external', function ($is_external, $host) {
            if (str_ends_with($host, 'localhost') || str_ends_with($host, '127.0.0.1') || str_ends_with($host, '.test')) {
                return true;
            }

            return $is_external;
        }, 10, 2);

        // Doing register client request if user is logged in.
        if (is_user_logged_in() && isset($_GET['state'], $_GET['response_type'], $_GET['approval_prompt'], $_GET['redirect_uri']) && filter_var($_GET['redirect_uri'], FILTER_VALIDATE_URL)) {
            $this->dispatchLoginEvents();
        }

        add_filter('woocommerce_account_menu_items', [$this, 'reorderMenuItems'], 9e2);
        add_filter('woocommerce_locate_template', [$this, 'overrideTemplates'], 10, 2);

        if (is_admin()) {
            // FIXME: Refactor this.
            $this->app->make(Meta::class);
        }

        add_action('save_post_product', [$this->app->make(ProductController::class), 'save'], 9e8, 3);
    }

    /**
     * Dispatch login events.
     *
     * @return void
     */
    protected function dispatchLoginEvents(): void
    {
        $user_id = get_current_user_id();
        $user_info_cache_key = 'blockera_api_user_info';
        $client_info_cache_key = 'blockera_api_client_info';

        if (!empty(get_user_meta($user_id, $client_info_cache_key))) {
            return;
        }

        $userCredentials = bsaGetUserAccessToken();

        $params = bsaGetRegisterClientParams();

        $client = bsaDoStoreClient($params, $userCredentials['token_type'] . ' ' . $userCredentials['access_token']);

        if (empty($client)) {
            return;
        }

        $client_id = $client['client_id'];
        $client_secret = $client['client_secret'];

        $params = [
            'params' => $params,
            'authorization' => $userCredentials['token_type'] . ' ' . $userCredentials['access_token'],
        ];

        $authorizeResponse = bsaDoAuthorization($client_id, $client_secret, $params);

        if (empty($authorizeResponse)) {
            delete_user_meta($user_id, $user_info_cache_key);
            delete_user_meta($user_id, $client_info_cache_key);

            return;
        }

        // Redirect to the client page.
        wp_redirect($authorizeResponse['redirect_to_client'] . "&client_id=$client_id&client_secret=$client_secret&redirect_to=" . urlencode($authorizeResponse['redirect_to_consent_page']), 302);
        // Stop further WordPress execution for this request.
        exit;
    }

    /**
     * Reorder the subscription menu item to the woocommerce my account menu items.
     *
     * @param array $items The menu items array.
     *
     * @return array Updated menu items array.
     */
    public function reorderMenuItems(array $items): array
    {
        unset($items['subscriptions']);
        unset($items['downloads']);

        $new_items = [];

        foreach ($items as $key => $item) {
            $new_items[$key] = $item;

            if ($key === 'dashboard') {
                $new_items['subscriptions'] = __('Licenses', 'blockera-site-toolkit');
            }
        }

        return $new_items;
    }

    /**
     * Override the default woocommerce templates.
     *
     * @param string $template The template path.
     * @param string $templateName The template name.
     *
     * @return string The template path.
     */
    public function overrideTemplates(string $template, string $templateName): string
    {
        if ('myaccount/my-subscriptions-view.php' === $templateName && false !== strpos($_SERVER['REQUEST_URI'], 'my-account/my-subscription')) {
            return $this->app->getPath() . '/vendor/blockera/site-toolkit/php/Views/licenses.php';
        }

        return $template;
    }
}
