<?php

namespace BlockeraAI\SiteToolkit\Providers;

use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * Class AssetsProvider providing all assets.
 *
 * @since 1.0.0
 */
class AssetsProvider extends \Blockera\Bootstrap\AssetsProvider
{
    /**
     * Store the loader identifier.
     *
     * @return string the loader identifier.
     */
    public function getId(): string
    {
        return 'blockera-site-toolkit-loader';
    }

    /**
     * Store the handler name.
     *
     * @return string the handler name.
     */
    public function getHandler(): string
    {
        return '@blockeraai/blockera-site-toolkit';
    }

    /**
     * Bootstrap any application services.
     *
     * @throws BindingResolutionException Binding resolution exception error handle.
     * @return void
     */
    public function boot(): void
    {
        add_filter('blockera/wordpress/' . $this->getId() . '/handle/inline-script', [$this, 'getHandler']);

        $this->app->make(
            $this->getId(),
            [
                'assets' => $this->getAssets(),
                'extra-args' => [
                    'fallback' => [
                        'url'  => $this->getURL(),
                        'path' => $this->getPATH(),
                    ],
                    'packages-deps' => [],
                ],
            ]
        );
    }

    /**
     * Get the plugin's root directory URL.
     *
     * @return string
     */
    protected function getURL(): string
    {
        return BSA_PLUGIN_URL;
    }

    /**
     * Get the plugin's root directory path.
     *
     * @return string
     */
    protected function getPath(): string
    {
        return BSA_PLUGIN_DIR;
    }

    /**
     * Get all assets of blockera plugin.
     *
     * @return array the assets list to load on page.
     */
    protected function getAssets(): array
    {
        return [
            'my-account-styles',
        ];
    }
}
