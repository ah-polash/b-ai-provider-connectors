<?php
/**
 * Registers enabled bundled and custom providers with the WP AI Client registry.
 *
 * Core's `_wp_connectors_register_default_ai_providers()` then creates a connector
 * for every registered provider automatically, and `_wp_connectors_pass_default_keys_to_ai_client()`
 * hands the stored API keys to the registry — exactly like the official
 * "AI Provider for Anthropic / OpenAI" plugins. This plugin never registers connectors itself.
 *
 * @package BAIProviderConnectors
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AI Client registration.
 *
 * @since 1.0.0
 */
class BAIOAP_AI_Client {

	/**
	 * Singleton instance.
	 *
	 * @var BAIOAP_AI_Client|null
	 */
	private static $instance = null;

	/**
	 * Returns the singleton instance.
	 *
	 * @return BAIOAP_AI_Client
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Registers WordPress hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		// Same timing as the official provider plugins — before core's connectors init on init:15.
		add_action( 'init', array( $this, 'register_providers' ), 5 );
	}

	/**
	 * Registers every enabled provider class.
	 *
	 * @return void
	 */
	public function register_providers() {
		foreach ( BAIOAP_Catalog::bundled() as $id => $class ) {
			if ( BAIOAP_Catalog::is_enabled( $id ) ) {
				self::register_class( $class );
			}
		}
		foreach ( BAIOAP_Catalog::custom() as $slot => $data ) {
			if ( BAIOAP_Catalog::is_enabled( $data['id'] ) ) {
				self::register_class( BAIOAP_Catalog::custom_class( $slot ) );
			}
		}
	}

	/**
	 * Registers a provider class with the AI Client registry if not already present.
	 *
	 * @param string $class Provider class name.
	 * @return bool True when the class is registered after the call.
	 */
	public static function register_class( $class ) {
		if ( ! class_exists( \WordPress\AiClient\AiClient::class ) || ! class_exists( $class ) ) {
			return false;
		}
		try {
			$registry = \WordPress\AiClient\AiClient::defaultRegistry();
			if ( ! $registry->hasProvider( $class ) ) {
				$registry->registerProvider( $class );
			}
			return true;
		} catch ( \Throwable $e ) {
			return false;
		}
	}

	/**
	 * Makes sure one of our providers is registered for the current request
	 * (used when testing or enabling a provider that was disabled at init).
	 *
	 * @param string $id Provider ID.
	 * @return bool
	 */
	public static function ensure_registered( $id ) {
		$class = BAIOAP_Catalog::class_for( $id );
		return $class ? self::register_class( $class ) : false;
	}
}
