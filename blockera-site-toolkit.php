<?php
/*
Plugin Name: Blockera Site Toolkit
Description: A site toolkit plugin for Blockera AI.
Version: 1.0
Author: blockera.ai
Tested up to: 6.7
Domain Path: /languages
Domain: blockera-site-toolkit
*/

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    die('Access Denied!');
}

require __DIR__ . '/vendor/autoload.php';

use BlockeraAI\SiteToolkit\Setup;
use BlockeraAI\SiteToolkit\Commands\Database;
use BlockeraAI\SiteToolkit\Database\Migrations;

define('BSA_PLUGIN_FILE', __FILE__);
define('BSA_PLUGIN_URL', plugin_dir_url(BSA_PLUGIN_FILE));
define('BSA_PLUGIN_DIR', plugin_dir_path(BSA_PLUGIN_FILE));
define('BSA_PLUGIN_MODE', 'dev');

$migrations = [
    new Migrations\ClientsTable(),
    new Migrations\AccessTokenTable(),
    new Migrations\RefreshTokenTable(),
];

/**
 * @var Setup $setup
 */
$setup = Setup::getInstance();

$setup->setMigrations(new Migrations($migrations))->mount()->unmount();

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

// Initialize the database commands.
if (BSA_PLUGIN_MODE === 'dev' && class_exists(\WP_CLI::class)) {
    $db = new Database();
    $db->migrate($migrations);

    $whoops = new \Whoops\Run;
    $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
    $whoops->register();
}
