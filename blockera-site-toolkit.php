<?php
/**
 * Plugin Name: Blockera Site Toolkit
 * Plugin URI: https://blockera.ai/
 * Description: Powers Blockera account licensing, OAuth connect flows, secure downloads, and WooCommerce product release tooling.
 * Version: 1.0
 * Author: blockera.ai
 * Requires at least: 6.6
 * Requires PHP: 7.4
 * Tested up to: 7.1
 * Domain Path: /languages
 * License: GPLv3 or later
 * Domain: blockera-site-toolkit
 *
 * @package BlockeraAI\SiteToolkit
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	// @debug-ignore
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
	$bsa_dotenv = Dotenv\Dotenv::createImmutable( __DIR__ );
	$bsa_dotenv->safeLoad();
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
$bsa_setup = BlockeraAI\SiteToolkit\Setup::getInstance();
### END AUTO-GENERATED FRONT CONTROLLERS

$bsa_setup->setPluginDir( BSA_PLUGIN_DIR );
$bsa_setup->setPluginUrl( BSA_PLUGIN_URL );
$bsa_setup->setPluginMode( BSA_PLUGIN_MODE );
$bsa_setup->setPluginFile( BSA_PLUGIN_FILE );

$bsa_setup->mount()->unmount();

/**
 * Initialize the plugin.
 *
 * @since 1.0.0
 */
add_action(
	'plugins_loaded',
	function () use ( $bsa_setup ): void {
		add_action(
			'init',
			static function () use ( $bsa_setup ): void {
				$bsa_setup->bootstrap();
			}
		);
	}
);

if ( 'development' === BSA_PLUGIN_MODE && class_exists( \Whoops\Run::class ) ) {
	$bsa_whoops = new \Whoops\Run();
	$bsa_whoops->pushHandler( new \Whoops\Handler\PrettyPageHandler() );
	$bsa_whoops->register();
}
