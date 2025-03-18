#!/usr/bin/env php
<?php

/**
 * Generates the production (plugin build) version of `blockera-site-toolkit.php`,
 * containing alternate `define` statements from the development version.
 *
 * @package blockera-site-toolkit-build
 */

$f = fopen(dirname(__DIR__) . '/blockera-site-toolkit.php', 'r');

$plugin_version = null;
$inside_defines = false;

/**
 * Prints `define` statements for the production version of `blockera-site-toolkit.php`
 * (the plugin entry point).
 */
function print_production_defines()
{
    global $plugin_version;

    echo "if (! defined('BSA_VERSION')) { define( 'BSA_VERSION', '$plugin_version' ); }\n";

    $git_commit = trim(shell_exec('git rev-parse HEAD'));

    echo "if (! defined('BSA_PLUGIN_MODE')) { define( 'BSA_PLUGIN_MODE', 'production' ); }\n";
    echo "if (! defined('BSA__GIT_COMMIT')) { define( 'BSAT__GIT_COMMIT', '$git_commit' ); }\n";
}

while (true) {
    $line = fgets($f);
    if (false === $line) {
        break;
    }

    if (
        ! $plugin_version &&
        preg_match('@^\s*\*\s*Version:\s*([0-9.]+)@', $line, $matches)
    ) {
        $plugin_version = $matches[1];
    }

    switch (trim($line)) {
        case '### BEGIN AUTO-GENERATED DEFINES':
            $inside_defines = true;
            echo $line;
            print_production_defines();
            break;

        case '### END AUTO-GENERATED DEFINES':
        case '### END AUTO-GENERATED FRONT CONTROLLERS':
            $inside_defines = false;
            echo $line;
            break;

        case '### BEGIN AUTO-GENERATED FRONT CONTROLLERS':
            $inside_defines = true;
            echo $line;
			// implement front controllers
            break;

        default:
            if (! $inside_defines) {
                echo $line;
            }
            break;
    }
}

fclose($f);
