<?php
/**
 * Plugin Name: Blockera Site Toolkit
 * Plugin URI: https://blockera.ai/
 * Description: Powers Blockera account licensing, OAuth connect flows, secure downloads, and WooCommerce product release tooling.
 * Version: 1.0
 * Author: blockera.ai
 * Requires at least: 6.6
 * Requires PHP: 8.2
 * Tested up to: 6.7
 * Domain Path: /languages
 * License: GPLv3 or later
 * Domain: blockera-site-toolkit
 *
 * @package BlockeraAI\SiteToolkit
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access Denied!' );
}

### BEGIN AUTO-GENERATED AUTOLOADER
require_once __DIR__ . '/packages/global-packages/packages/autoloader-coordinator/bootstrap.php';
blockera_bootstrap_shared_autoloader(
	'blockera-site-toolkit',
	__DIR__,
	[
		'priority'          => 5,
		'default'           => ! defined( 'BSA_PLUGIN_FILE' ) || BSA_PLUGIN_FILE === __FILE__,
		'file'              => __FILE__,
		'entry_constant'    => 'BSA_PLUGIN_FILE',
		// Prefer Free / Pro / One shared packages when those products are active.
		'defer_files_until' => [ 'blockera', 'blockera-one' ],
		'companions'        => [
			[
				'slug'           => 'blockera',
				'plugin_file'    => 'blockera/blockera.php',
				'entry_constant' => 'BLOCKERA_SB_FILE',
			],
			[
				'slug'           => 'blockera-pro',
				'plugin_file'    => 'blockera-pro/blockera-pro.php',
				'entry_constant' => 'BLOCKERA_PRO_FILE',
			],
			[
				'slug'             => 'blockera-one',
				'type'             => 'theme',
				'theme_stylesheet' => 'blockera-one',
			],
		],
	]
);
### END AUTO-GENERATED AUTOLOADER

if ( file_exists( __DIR__ . '/.env' ) ) {
	// Env Loading ...
	$dotenv = Dotenv\Dotenv::createImmutable( __DIR__ );
	$dotenv->safeLoad();
}

if ( ! defined( 'BSA_PLUGIN_FILE' ) ) {
	define( 'BSA_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'BSA_PLUGIN_URL' ) ) {
	define( 'BSA_PLUGIN_URL', plugin_dir_url( BSA_PLUGIN_FILE ) );
}

if ( ! defined( 'BSA_PLUGIN_DIR' ) ) {
	define( 'BSA_PLUGIN_DIR', plugin_dir_path( BSA_PLUGIN_FILE ) );
}

### BEGIN AUTO-GENERATED DEFINES
if ( ! defined( 'BSA_PLUGIN_MODE' ) ) {
	define( 'BSA_PLUGIN_MODE', 'development' );
}
### END AUTO-GENERATED DEFINES

### BEGIN AUTO-GENERATED FRONT CONTROLLERS
$setup = BlockeraAI\SiteToolkit\Setup::getInstance();
### END AUTO-GENERATED FRONT CONTROLLERS

$setup->setPluginDir( BSA_PLUGIN_DIR );
$setup->setPluginUrl( BSA_PLUGIN_URL );
$setup->setPluginMode( BSA_PLUGIN_MODE );
$setup->setPluginFile( BSA_PLUGIN_FILE );

$setup->mount()->unmount();

/**
 * Initialize the plugin.
 *
 * @since 1.0.0
 */
add_action(
	'plugins_loaded',
	function () use ( $setup ): void {
		add_action(
			'init',
			static function () use ( $setup ): void {
				$setup->bootstrap();
			}
		);
	}
);

if ( 'development' === BSA_PLUGIN_MODE && class_exists( \Whoops\Run::class ) ) {
	$whoops = new \Whoops\Run();
	$whoops->pushHandler( new \Whoops\Handler\PrettyPageHandler() );
	$whoops->register();
}
