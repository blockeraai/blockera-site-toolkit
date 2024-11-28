<?php

namespace Blockera\Bootstrap;

use Blockera\WordPress\AssetsLoader;

abstract class AssetsProvider extends ServiceProvider
{

	/**
	 * Register any application services.
	 *
	 * @return void
	 */
	public function register(): void
	{

		$this->app->bind(
			$this->getId(),
			function (Application $app, array $args = []) {

				return new AssetsLoader(
					$app,
					$args['assets'],
					array_merge(
						[
							'id'         => $this->getId(),
							'root'       => [
								'url'  => $this->getURL(),
								'path' => $this->getPath(),
							],
							'debug-mode' => $app->isDebug(),
						],
						$args['extra-args']
					)
				);
			}
		);
	}

	/**
	 * Retrieve handler name.
	 *
	 * @return string the WordPress enqueue APIs ("wp_enqueue_script"(s) or "wp_enqueue_style"(s)) handle name.
	 */
	abstract public function getHandler(): string;

	/**
	 * Get all assets of blockera plugin.
	 *
	 * @return array the assets list to load on page.
	 */
	protected function getAssets(): array
	{

		return [];
	}

	/**
	 * @return string The loader identify.
	 */
	abstract public function getId(): string;

	/**
	 * @return string the blockera plugin root URL.
	 */
	protected function getURL(): string
	{

		return $this->app->getURL();
	}

	/**
	 * @return string the blockera plugin root PATH.
	 */
	protected function getPATH(): string
	{

		return $this->app->getPath();
	}
}
