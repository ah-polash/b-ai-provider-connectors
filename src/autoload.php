<?php
/**
 * PSR-4 autoloader for the plugin's AI provider classes.
 *
 * Maps the namespace prefix `BPlugins\AllInOneAIProviders\` to the `src/` directory.
 *
 * @package BPluginsAIProviderConnectors
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

spl_autoload_register(
	static function ( $class ) {
		$prefix = 'BPlugins\\AllInOneAIProviders\\';
		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}

		$relative = substr( $class, strlen( $prefix ) );
		$relative = str_replace( '\\', DIRECTORY_SEPARATOR, $relative );
		$file     = BAIOAP_DIR . 'src/' . $relative . '.php';

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);
