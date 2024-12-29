<?php

namespace BlockeraAI\SiteToolkit;

use Blockera\Bootstrap\Application;
use League\OAuth2\Server\ResourceServer;
use League\OAuth2\Server\AuthorizationServer;
use BlockeraAI\SiteToolkit\Providers\AssetsProvider;
use BlockeraAI\SiteToolkit\Providers\AppServiceProvider;
use League\OAuth2\Server\Middleware\ResourceServerMiddleware;

class Setup extends Application
{
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
     * Setup constructor.
     */
    public function __construct()
    {
        // Register the service providers.
        $this->service_providers = [
            AssetsProvider::class,
            AppServiceProvider::class,
        ];

        parent::__construct();
    }

    /**
     * Adds a rewrite rule that transforms a URL structure to a set of query vars.
     *
     * @return void
     */
    public function rewriteRules(): void
    {
        add_rewrite_rule('^authorize/?$', 'index.php?authorize=true', 'top');
        add_rewrite_rule('^consent-form/?$', 'index.php?consent-form=true', 'top');
    }

    /**
     * Register all routes.
     *
     * @return void
     */
    public function registerRoutes(): void
    {
        add_action('rest_api_init', [$this, 'registerRestRoutes']);

        // Rewrite rule to transform url specific page to query vars.
        $this->rewriteRules();

        $web_filename = BSA_PLUGIN_DIR . '/src/Routes/web.php';

        if (file_exists($web_filename)) {
            // Require the web routes.
            require_once $web_filename;
        }
    }

    /**
     * Register REST API routes.
     *
     * @return void
     */

    public function registerRestRoutes(): void
    {
        $api_filename = BSA_PLUGIN_DIR . '/src/Routes/api.php';

        if (file_exists($api_filename)) {
            // Require the API routes.
            require_once BSA_PLUGIN_DIR . '/src/Routes/api.php';
        }
    }

    /**
     * Mounting the plugin ...
     *
     * @return self
     */
    public function mount(): self
    {
        // Register activation and deactivation hooks.
        register_activation_hook(BSA_PLUGIN_FILE, [$this, 'activate']);

        return $this;
    }

    /**
     * Activate the plugin.
     *
     * @return void
     */
    public function activate(): void
    {
        // Rewrite rules.
        $this->rewriteRules();
        flush_rewrite_rules();
    }

    /**
     * Unmounting the plugin ...
     *
     * @return self
     */
    public function unmount(): self
    {
        // Register uninstall hook.
        register_deactivation_hook(BSA_PLUGIN_FILE, 'flush_rewrite_rules');

        return $this;
    }

    /**
     * Get the resource server instance.
     *
     * @return ResourceServer
     */
    public function getResourceServer(): ResourceServer
    {
        if (!$this->resourceServer) {
            $this->make(ResourceServerMiddleware::class, [$this->resourceServer]);
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
    public function setResourceServer(ResourceServer $server): void
    {
        $this->resourceServer = $server;
    }

    /**
     * Get the OAuth server instance.
     *
     * @return  AuthorizationServer
     */
    public function getAuthorizationServer(): AuthorizationServer
    {
        return $this->authorizationServer;
    }

    /**
     * Set the OAuth server instance.
     *
     * @param  AuthorizationServer $server The OAuth server instance.
     *
     * @return void
     */
    public function setAuthorizationServer(AuthorizationServer $server)
    {
        $this->authorizationServer = $server;
    }

    /**
     * Get the plugin URL.
     *
     * @return string
     */
    public function getURL(): string
    {
        return BSA_PLUGIN_URL;
    }

    /**
     * Get the plugin path.
     *
     * @return string
     */
    public function getPath(): string
    {
        return BSA_PLUGIN_DIR;
    }

    /**
     * Check if the plugin is in debug mode.
     *
     * @return bool true if the plugin is in debug mode, false otherwise.
     */
    public function isDebug(): bool
    {
        return defined('BSA_PLUGIN_MODE') && 'dev' === BSA_PLUGIN_MODE;
    }
}
