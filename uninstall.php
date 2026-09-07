<?php
/**
 * Removes the plugin's options when it is deleted.
 *
 * API keys for the bundled providers are left in place: they live under core's
 * `connectors_ai_{provider}_api_key` options and would be reused if the plugin is
 * reinstalled. Keys for custom providers are removed because nothing else can use them.
 *
 * @package BPluginsAIProviderConnectors
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$baioap_custom = get_option( 'baioap_custom_providers', array() );
if ( is_array( $baioap_custom ) ) {
	foreach ( $baioap_custom as $baioap_provider ) {
		if ( is_array( $baioap_provider ) && ! empty( $baioap_provider['id'] ) ) {
			delete_option( 'connectors_ai_' . str_replace( '-', '_', sanitize_key( (string) $baioap_provider['id'] ) ) . '_api_key' );
		}
	}
}

delete_option( 'baioap_custom_providers' );
delete_option( 'baioap_disabled_providers' );
delete_option( 'baioap_provider_priority' );
