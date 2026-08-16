<?php

namespace BlockeraAI\SiteToolkit\Providers;

use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * Class AssetsProvider providing all assets.
 *
 * @since 1.0.0
 */
class AssetsProvider extends \Blockera\Bootstrap\AssetsProvider {

    /**
     * Store the loader identifier.
     *
     * @return string the loader identifier.
     */
    public function getId(): string {
        return 'blockera-site-toolkit-loader';
    }

    /**
     * Store the handler name.
     *
     * @return string the handler name.
     */
    public function getHandler(): string {
        return '@blockeraai/blockera-site-toolkit';
    }

    /**
     * Bootstrap any application services.
     *
     * @throws BindingResolutionException Binding resolution exception error handle.
     * @return void
     */
    public function boot(): void {
        add_filter('blockera/wordpress/' . $this->getId() . '/handle/inline-script', [ $this, 'getHandler' ]);

        if (
			( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'licenses' ) )
			|| str_starts_with( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), '/my-account/licenses' )
			|| str_starts_with( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), '/consent-form' )
		) {
            $this->app->make(
                $this->getId(),
                [
                    'assets' => $this->getAssets(),
                    'extra-args' => [
                        'root' => [
                            'url'  => $this->getURL(),
                            'path' => $this->getPATH(),
                        ],
						'debug-mode' => $this->getDebugMode(),
                        'packages-deps' => [],
                    ],
                ]
            );

            add_action(
                'wp_enqueue_scripts',
                function () {
					// Enqueue Gutenberg component styles and scripts.
					wp_enqueue_script(
                        'gutenberg-components',
                        includes_url('/js/dist/components.min.js'),
                        [ 'wp-element', 'wp-i18n', 'wp-api-fetch' ],
                        false,
                        true
					);

					// Optionally enqueue style dependencies.
					wp_enqueue_style(
                        'wp-components-style',
                        includes_url('/css/dist/components/style.min.css'),
                        [],
                        false
					);
				}
            );
        }
    }

    /**
     * Get the plugin's root directory URL.
     *
     * @return string
     */
    protected function getURL(): string {
        return $this->app->getURL();
    }

    /**
     * Get the plugin's root directory path.
     *
     * @return string
     */
    protected function getPath(): string {
        return $this->app->getPath();
    }

	/**
	 * Get the debug mode.
	 *
	 * @return bool
	 */
	protected function getDebugMode(): bool {
		return $this->app->isDebug();
	}

    /**
     * Get all assets of blockera plugin.
     *
     * @return array the assets list to load on page.
     */
    protected function getAssets(): array {
        return [
            'utils',
			'storage',
            'classnames',
			'icons',
            'data-editor',
            'env',
			'data',
            'controls',
            'bootstrap',
            'site-toolkit',
            'controls-styles',
            'site-toolkit-styles',
        ];
    }
}
