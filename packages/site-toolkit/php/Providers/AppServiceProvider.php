<?php

namespace BlockeraAI\SiteToolkit\Providers;

use BlockeraAI\SiteToolkit\Setup;
use Blockera\Bootstrap\Application;
use Blockera\Bootstrap\ServiceProvider;
use BlockeraAI\SiteToolkit\Meta\Factory as Meta;
use BlockeraAI\SiteToolkit\Repositories\OrderRepository;
use BlockeraAI\SiteToolkit\Repositories\LicenseRepository;
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
		$this->app->singleton(LicenseRepository::class);

		$this->app->singleton(OrderRepository::class, function (Application $app, array $args = []) {
			$orders = wc_get_orders([
				'customer_id' => get_current_user_id(),
				'status' => ['completed'],
				'limit' => -1
			]);

			return new OrderRepository($app, $orders, $args['context'] ?? '');
		});
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

        // Doing register client request if user is logged in.
		// This is a workaround for the oauth2 redirect uri and not any other use case.
        if (is_user_logged_in() && isset($_GET['state'], $_GET['response_type'], $_GET['approval_prompt'], $_GET['redirect_uri']) && filter_var($_GET['redirect_uri'], FILTER_VALIDATE_URL)) {
            $this->dispatchLoginEvents();
        }

        add_filter('woocommerce_account_menu_items', [$this, 'reorderMenuItems'], 9e2);
		add_filter('woocommerce_account_licenses_endpoint', [$this, 'getLicensesTemplate']);

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
		$params = bsaGetRegisterClientParams();
        $user_id = get_current_user_id();
		$domain = $params['domain'];

		// If the domain is not set, then we need to return.
		if(empty($domain)){
			return;
		}

		// Cache keys for the user info and client info.
        $user_info_cache_key = 'blockera_api_user_info_' . md5($domain);
        $client_info_cache_key = 'blockera_api_client_info_' . md5($domain);

		$userCredentials = bsaGetUserAccessToken($user_info_cache_key);
		$authorization = $userCredentials['token_type'] . ' ' . $userCredentials['access_token'];
		$clientCredentials = get_user_meta($user_id, $client_info_cache_key, true);

		// If the user is logged in and the authorized is not set, then we need to authorize the client.
		// This is a first try to refresh the client credentials and connection.
        if (!empty($clientCredentials) && empty($_GET['authorized']) && empty($_GET['product'])) { 
			// We should the authorize the client if the client registered previously.
			$client_id = $clientCredentials['client_id'];
			$client_secret = $clientCredentials['client_secret'];

			$params = [
				'params' => $client_id && $client_secret ? array_merge($params, [
					'client_id' => $client_id,
					'client_secret' => $client_secret,
				]) : $params,
				'authorization' => $userCredentials['token_type'] . ' ' . $userCredentials['access_token'],
			];

			$authorizeResponse = bsaDoAuthorization($params);

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

		if(empty($userCredentials) || !empty($_GET['product'])){
			$_COOKIE['token_key'] = $client_info_cache_key;
			return;
		}

        $client = bsaDoStoreClient($params, $authorization, $client_info_cache_key);

        if (empty($client)) {
            return;
        }

        $client_id = $client['client_id'];
        $client_secret = $client['client_secret'];

        $params = [
            'params' => $client_id && $client_secret ? array_merge($params, [
                'client_id' => $client_id,
                'client_secret' => $client_secret,
            ]) : $params,
            'authorization' => $userCredentials['token_type'] . ' ' . $userCredentials['access_token'],
        ];

        $authorizeResponse = bsaDoAuthorization($params);

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
                $new_items['licenses'] = __('Licenses', 'blockera-site-toolkit');
            }
        }

        return $new_items;
    }

	/**
	 * Get the licenses template.
	 *
	 * @return void
	 */
	public function getLicensesTemplate(): void
	{		
		if (!function_exists('wc_get_template')) {
			return;
		}

		$build_file = $this->app->getPath() . '/vendor/blockera/build/src/SiteToolkit/Views/licenses.php';

		if (file_exists($build_file)) {
			$default_path = $this->app->getPath() . '/vendor/blockera/build/src/SiteToolkit/';
		}else{ 
			$default_path = $this->app->getPath() . '/vendor/blockera/site-toolkit/php/';
		}

		$mappedLicenses = $this->app->make(OrderRepository::class)->getLicenses();

		wc_get_template(
			'Views/licenses.php',
			compact('mappedLicenses'),
			'',
			$default_path
		);
	}
}
