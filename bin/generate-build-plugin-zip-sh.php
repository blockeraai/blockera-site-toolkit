#!/usr/bin/env php
<?php
/**
 * Generates the production (plugin build) version of bin/build-plugin-zip.sh,
 * injecting vendor/blockera package path patterns for this consumer.
 *
 * Discovers packages from:
 * - Local packages/<name>/php (e.g. site-toolkit)
 * - blockera/* entries in composer.json require (shared global-packages)
 *
 * @package blockera-site-toolkit-build
 */

$root = dirname( __DIR__ );
$f    = fopen( $root . '/bin/build-plugin-zip.sh', 'r' );

$packages = [];

foreach ( (array) glob( $root . '/packages/*' ) as $package_path ) {
	$package_name = str_replace( $root . '/packages/', '', $package_path );

	if ( 'global-packages' === $package_name || preg_match( '/^dev-/', $package_name ) ) {
		continue;
	}

	if ( ! is_dir( $package_path . '/php' ) && ! is_dir( $package_path . '/core/php' ) ) {
		continue;
	}

	if ( 'blocks' === $package_name ) {
		$package_name .= '-core';
	}

	$packages[] = $package_name;
}

$composer_json = $root . '/composer.json';
if ( is_readable( $composer_json ) ) {
	$composer = json_decode( (string) file_get_contents( $composer_json ), true );
	foreach ( array_keys( (array) ( $composer['require'] ?? [] ) ) as $requirement ) {
		if ( 0 !== strpos( $requirement, 'blockera/' ) ) {
			continue;
		}

		$packages[] = substr( $requirement, strlen( 'blockera/' ) );
	}
}

$packages = array_values( array_unique( $packages ) );

$internal_packages = array_values(
	array_filter(
		$packages,
		static function ( string $package_name ): bool {
			return ! preg_match( '/-sdk$/', $package_name );
		}
	)
);

$sdks = array_values( array_diff( $packages, $internal_packages ) );

$inside_pattern_block = false;

while ( true ) {
	$line = fgets( $f );
	if ( false === $line ) {
		break;
	}

	switch ( trim( $line ) ) {

		case '### END AUTO-GENERATED VENDOR PACKAGES PATH PATTERN':
			$inside_pattern_block = false;
			break;

		case '### BEGIN AUTO-GENERATED VENDOR PACKAGES PATH PATTERN':
			$inside_pattern_block = true;

			echo implode(
				PHP_EOL,
				array_map(
					static function ( string $name ): string {
						return sprintf(
							'	$(find ./vendor/blockera/%1$s/ -type f \( -name "*.php" -o -name "*.json" \)) \\',
							$name
						);
					},
					$internal_packages
				)
			);

			if ( ! empty( $sdks ) ) {
				echo PHP_EOL;
			}

			echo implode(
				PHP_EOL,
				array_map(
					static function ( string $name ): string {
						return sprintf(
							'	$(find ./vendor/blockera/%1$s/) \\',
							$name
						);
					},
					$sdks
				)
			);

			echo PHP_EOL;

			break;

		default:
			if ( ! $inside_pattern_block ) {
				echo $line;
			}
			break;
	}
}

fclose( $f );
