<?php
/**
 * Direct access is not allowed.
 *
 * @package config/assets.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return [
	'frontend' => [
		'list'      => [
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
		],
		'with-deps' => [],
	],
];
