<?php
/*
Plugin Name: Blockera Site Toolkit
Description: A site toolkit plugin for Blockera AI.
Version: 1.0
Author: blockera.ai
Tested up to: 6.7
Domain Path: /languages
License: GPLv3 or later
Domain: blockera-site-toolkit
*/

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    die('Access Denied!');
}

require __DIR__ . '/vendor/autoload.php';

// Env Loading ...
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

define('BSA_PLUGIN_FILE', __FILE__);
define('BSA_PLUGIN_URL', plugin_dir_url(BSA_PLUGIN_FILE));
define('BSA_PLUGIN_DIR', plugin_dir_path(BSA_PLUGIN_FILE));
### BEGIN AUTO-GENERATED DEFINES
define('BSA_PLUGIN_MODE', 'dev');
### END AUTO-GENERATED DEFINES

### BEGIN AUTO-GENERATED FRONT CONTROLLERS
$setup = BlockeraAI\SiteToolkit\Setup::getInstance();
### END AUTO-GENERATED FRONT CONTROLLERS

$setup->setPluginDir(BSA_PLUGIN_DIR);
$setup->setPluginUrl(BSA_PLUGIN_URL);
$setup->setPluginMode(BSA_PLUGIN_MODE);
$setup->setPluginFile(BSA_PLUGIN_FILE);

$setup->mount()->unmount();

/**
 * Initialize the plugin.
 *
 * @since 1.0.0
 */
add_action('plugins_loaded', function () use ($setup): void {
    add_action('init', static function () use ($setup): void {
        $setup->bootstrap();
    });
});

if('true' === bsaGetConfig('debug')){
	$whoops = new \Whoops\Run();
	$whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler());
	$whoops->register();
}
