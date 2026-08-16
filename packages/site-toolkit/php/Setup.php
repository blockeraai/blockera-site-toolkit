<?php
// phpcs:disable WordPress.NamingConventions.ValidVariableName -- Existing camelCase properties match the public Setup API.

namespace BlockeraAI\SiteToolkit;

use Blockera\Bootstrap\Application;
use League\OAuth2\Server\ResourceServer;
use League\OAuth2\Server\AuthorizationServer;
use BlockeraAI\SiteToolkit\Providers\AssetsProvider;
use BlockeraAI\SiteToolkit\Providers\AppServiceProvider;
use League\OAuth2\Server\Middleware\ResourceServerMiddleware;

class Setup extends Application {

    /**
     * Store the OAuth server instance.
     *
     * @var AuthorizationServer The OAuth server instance.
     */
    private AuthorizationServer $authorizationServer;

    /**
     * Store the resource server instance.
     *
     * @var ResourceServer The resource server instance.
     */
    private ResourceServer $resourceServer;

    /**
     * Store the plugin directory.
     *
     * @var string
     */
    private string $pluginDir;

    /**
     * Store the plugin URL.
     *
     * @var string
     */
    private string $pluginUrl;

    /**
     * Store the plugin mode.
     *
     * @var string
     */
    private string $pluginMode;

    /**
     * Store the plugin file.
     *
     * @var string
     */
    private string $pluginFile;

    /**
     * Setup constructor.
     */
    public function __construct() {
        // Register the service providers.
        $this->service_providers = [
            AssetsProvider::class,
            AppServiceProvider::class,
        ];

        parent::__construct();
    }

    /**
     * Set the plugin directory.
     *
     * @param string $pluginDir The plugin directory.
     * @return void
     */
    public function setPluginDir( string $pluginDir): void {
        $this->pluginDir = $pluginDir;
    }

    /**
     * Set the plugin URL.
     *
     * @param string $pluginUrl The plugin URL.
     * @return void
     */
    public function setPluginUrl( string $pluginUrl): void {
        $this->pluginUrl = $pluginUrl;
    }

    /**
     * Get the plugin URL.
     *
     * @return string
     */
    public function getIURL(): string {
        return $this->pluginUrl;
    }

    /**
     * Set the plugin mode.
     *
     * @param string $pluginMode The plugin mode.
     * @return void
     */
    public function setPluginMode( string $pluginMode): void {
        $this->pluginMode = $pluginMode;
    }

    /**
     * Get the plugin mode.
     *
     * @return string
     */
    public function getPluginMode(): string {
        return $this->pluginMode;
    }

    /**
     * Set the plugin file.
     *
     * @param string $pluginFile The plugin file.
     * @return void
     */
    public function setPluginFile( string $pluginFile): void {
        $this->pluginFile = $pluginFile;
    }

    /**
     * Get the plugin file.
     *
     * @return string
     */
    public function getPluginFile(): string {
        return $this->pluginFile;
    }

    /**
     * Adds a rewrite rule that transforms a URL structure to a set of query vars.
     *
     * @return void
     */
    public function rewriteRules(): void {
        add_rewrite_rule('^authorize/?$', 'index.php?authorize=true', 'top');
        add_rewrite_rule('^consent-form/?$', 'index.php?consent-form=true', 'top');

		global $wp_rewrite;

		$wp_rewrite->flush_rules();
    }

    /**
     * Register all routes.
     *
     * @return void
     */
    public function registerRoutes(): void {
        add_action('rest_api_init', [ $this, 'registerRestRoutes' ]);

        // Rewrite rule to transform url specific page to query vars.
        $this->rewriteRules();

		$build_filename = $this->getPath() . '/vendor/blockera/build/src/SiteToolkit/Routes/web.php';
        $web_filename   = $this->getPath() . '/vendor/blockera/site-toolkit/php/Routes/web.php';

		if (file_exists($build_filename)) {
			require_once $build_filename;
		} elseif (file_exists($web_filename)) {
            // Require the web routes.
            require_once $web_filename;
        }
    }

    /**
     * Register REST API routes.
     *
     * @return void
     */
    public function registerRestRoutes(): void {

		$build_file = $this->getPath() . '/vendor/blockera/build/src/SiteToolkit/Routes/api.php';
		
        $apiFilename = $this->getPath() . '/vendor/blockera/site-toolkit/php/Routes/api.php';

		if (file_exists($build_file)) {
			require_once $build_file;
		} elseif (file_exists($apiFilename)) {
            // Require the API routes.
            require_once $apiFilename;
        }
    }

    /**
     * Mounting the plugin ...
     *
     * @return self
     */
    public function mount(): self {
        // Register activation and deactivation hooks.
        register_activation_hook($this->getPluginFile(), [ $this, 'activate' ]);

        add_action('init', [ $this, 'addEndpoint' ]);

        return $this;
    }

    /**
     * Activate the plugin.
     *
     * @return void
     */
    public function activate(): void {
        // Rewrite rules.
        $this->rewriteRules();
        flush_rewrite_rules();
    }

    /**
     * Add the endpoint for the pages in my account to manage the subscription list and view.
     *
     * @since 1.0.0
     */
    public function addEndpoint() {
		if ( ! function_exists( 'WC' ) || ! WC()->query ) {
			return;
		}

		// Keep query var in sync and ensure rewrite endpoint exists even if hook order varies.
        WC()->query->query_vars['licenses'] = 'licenses';
		add_rewrite_endpoint( 'licenses', WC()->query->get_endpoints_mask() );
    }

    /**
     * Unmounting the plugin ...
     *
     * @return self
     */
    public function unmount(): self {
        // Register uninstall hook.
        register_deactivation_hook($this->getPluginFile(), 'flush_rewrite_rules');

        return $this;
    }

    /**
     * Get the resource server instance.
     *
     * @return ResourceServer
     */
    public function getResourceServer(): ResourceServer {
        if (! $this->resourceServer) {
            $this->make(ResourceServerMiddleware::class, [ $this->resourceServer ]);
        }

        return $this->resourceServer;
    }

    /**
     * Set the resource server instance.
     *
     * @param ResourceServer $server The resource server instance.
     *
     * @return void
     */
    public function setResourceServer( ResourceServer $server): void {
        $this->resourceServer = $server;
    }

    /**
     * Get the OAuth server instance.
     *
     * @return  AuthorizationServer
     */
    public function getAuthorizationServer(): AuthorizationServer {
        return $this->authorizationServer;
    }

    /**
     * Set the OAuth server instance.
     *
     * @param  AuthorizationServer $server The OAuth server instance.
     *
     * @return void
     */
    public function setAuthorizationServer( AuthorizationServer $server) {
        $this->authorizationServer = $server;
    }

    /**
     * Get the plugin path.
     *
     * @return string
     */
    public function getPath(): string {
        return $this->pluginDir;
    }

    /**
     * Get the plugin url.
     *
     * @return string
     */
    public function getURL(): string {
        return $this->pluginUrl;
    }

    /**
     * Check if the plugin is in debug mode.
     *
     * @return bool true if the plugin is in debug mode, false otherwise.
     */
    public function isDebug(): bool {
        return 'dev' === $this->getPluginMode();
    }
}
