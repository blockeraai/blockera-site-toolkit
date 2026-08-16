<?php
/**
 * Plugin Name: Blockera Site Toolkit
 * Description: A site toolkit plugin for Blockera AI.
 * Version: 1.0
 * Author: blockera.ai
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
// Coordinator only includes Composer require-dev (e.g. Whoops) when development runtime is set.
// Signal before bootstrap; BSA_PLUGIN_MODE is defined later and is not read by the coordinator.
if ( ! isset( $_ENV['APP_MODE'] ) && false === getenv( 'APP_MODE' ) ) {
	$_ENV['APP_MODE'] = 'development';
	putenv( 'APP_MODE=development' );
}
require_once __DIR__ . '/packages/global-packages/packages/autoloader-coordinator/bootstrap.php';
blockera_bootstrap_shared_autoloader(
	'blockera-site-toolkit',
	__DIR__,
	[
		'priority'       => 10,
		'default'        => ! defined( 'BSA_PLUGIN_FILE' ) || BSA_PLUGIN_FILE === __FILE__,
		'file'           => __FILE__,
		'entry_constant' => 'BSA_PLUGIN_FILE',
	]
);
### END AUTO-GENERATED AUTOLOADER

// Env Loading ...
$dotenv = Dotenv\Dotenv::createImmutable( __DIR__ );
$dotenv->safeLoad();

define( 'BSA_PLUGIN_FILE', __FILE__ );
define( 'BSA_PLUGIN_URL', plugin_dir_url( BSA_PLUGIN_FILE ) );
define( 'BSA_PLUGIN_DIR', plugin_dir_path( BSA_PLUGIN_FILE ) );
### BEGIN AUTO-GENERATED DEFINES
define( 'BSA_PLUGIN_MODE', 'dev' );
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

if ( 'dev' === BSA_PLUGIN_MODE && class_exists( \Whoops\Run::class ) ) {
	$whoops = new \Whoops\Run();
	$whoops->pushHandler( new \Whoops\Handler\PrettyPageHandler() );
	$whoops->register();
}
