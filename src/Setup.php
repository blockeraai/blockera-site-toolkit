<?php

namespace BlockeraAI\SiteToolkit;

use Blockera\Bootstrap\Application;
use League\OAuth2\Server\AuthorizationServer;
use BlockeraAI\SiteToolkit\Providers\AssetsProvider;
use BlockeraAI\SiteToolkit\Database\Contracts\Command;
use BlockeraAI\SiteToolkit\Providers\AppServiceProvider;

class Setup extends Application
{
    /**
     * Store the OAuth server instance.
     *
     * @var AuthorizationServer The OAuth server instance.
     */
    private AuthorizationServer $server;

    /**
     * Migrations instance.
     *
     * @var Command $migrations The instance of Migrations object.
     */
    protected static Command $migrations;

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
     * Initializing Blockera Site Toolkit Setup.
     *
     * @return void
     */
    public function init(): void
    {
        // Configuring the OAuth server.
        add_action('init', [$this, 'configServer']);
    }

    /**
     * Adds a rewrite rule that transforms a URL structure to a set of query vars.
     *
     * @return void
     */
    public function rewriteRules(): void
    {
        add_rewrite_rule('^authorize$', 'index.php?authorize=true', 'top');
        add_rewrite_rule('^register-license$', 'index.php?register-license=true', 'top');
        add_rewrite_rule('^my-account\/license-manager\/add$', 'index.php?license-action=add', 'top');
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
     * Get the OAuth server instance.
     *
     * @return  AuthorizationServer
     */
    public function getServer(): AuthorizationServer
    {
        return $this->server;
    }

    /**
     * Set $migrations The instance of Migrations object.
     *
     * @param  Command $migrations The array of migrations instances.
     *
     * @return self
     */
    public function setMigrations(Command $migrations): self
    {
        self::$migrations = $migrations;

        return self::getInstance();
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

        // Executing the migrations.
        self::$migrations->execute();
    }

    /**
     * Unmounting the plugin ...
     *
     * @return self
     */
    public function unmount(): self
    {
        // Register uninstall hook.
        register_uninstall_hook(BSA_PLUGIN_FILE, [self::class, 'uninstall']);

        return $this;
    }

    /**
     * Uninstall the plugin.
     *
     * @return void
     */
    public static function uninstall(): void
    {
        flush_rewrite_rules();

        self::$migrations->undo();
    }

    /**
     * Set the OAuth server instance.
     *
     * @param  AuthorizationServer $server The OAuth server instance.
     *
     * @return void
     */
    public function setServer(AuthorizationServer $server)
    {
        $this->server = $server;
    }

    public function getURL(): string
    {
        return BSA_PLUGIN_URL;
    }

    public function getPath(): string
    {
        return BSA_PLUGIN_DIR;
    }

    public function isDebug(): bool
    {
        return defined('BSA_PLUGIN_MODE') && 'dev' === BSA_PLUGIN_MODE;
    }
}
