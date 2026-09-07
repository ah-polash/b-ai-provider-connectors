<?php
/**
 * Provider priority — reorders the AI Client registry per the saved order.
 *
 * The WP AI Client picks the first capable configured provider it encounters
 * when walking `ProviderRegistry::findModelsMetadataForSupport()`. That walk
 * uses the registry's internal insertion order. This class applies the site
 * owner's order by reordering the registry's private `$registeredIdsToClassNames`
 * array via reflection just after every other provider has registered.
 *
 * @package BAIProviderConnectors
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reflection-driven registry reorder.
 *
 * @since 1.0.0
 */
class BAIOAP_Priority {

	const OPTION_NAME = 'baioap_provider_priority';

	/**
	 * Singleton instance.
	 *
	 * @var BAIOAP_Priority|null
	 */
	private static $instance = null;

	/**
	 * Returns the singleton instance.
	 *
	 * @return BAIOAP_Priority
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
		// init:25 runs after `_wp_connectors_init` (init:15),
		// `_wp_register_default_connector_settings` (init:20), and
		// `_wp_connectors_pass_default_keys_to_ai_client` (init:20).
		add_action( 'init', array( $this, 'apply_priority_to_registry' ), 25 );
	}

	/**
	 * Returns the saved priority order (array of provider IDs).
	 *
	 * @return list<string>
	 */
	public function get_saved_order() {
		$saved = get_option( self::OPTION_NAME, array() );
		if ( ! is_array( $saved ) ) {
			return array();
		}
		return array_values( array_filter( array_map( 'sanitize_key', array_filter( $saved, 'is_string' ) ) ) );
	}

	/**
	 * Saves a new order and applies it to the current request's registry.
	 *
	 * @param list<string> $order Provider IDs.
	 * @return list<string> The sanitized order that was saved.
	 */
	public function save_order( array $order ) {
		$clean = array();
		foreach ( $order as $id ) {
			if ( ! is_string( $id ) ) {
				continue;
			}
			$id = sanitize_key( $id );
			if ( '' !== $id && ! in_array( $id, $clean, true ) ) {
				$clean[] = $id;
			}
		}
		update_option( self::OPTION_NAME, $clean );
		$this->apply_priority_to_registry();
		return $clean;
	}

	/**
	 * Removes the custom order.
	 *
	 * @return void
	 */
	public function reset_order() {
		delete_option( self::OPTION_NAME );
	}

	/**
	 * Registered provider IDs in effective (priority-applied) order.
	 *
	 * @return list<string>
	 */
	public function effective_order() {
		if ( ! class_exists( \WordPress\AiClient\AiClient::class ) ) {
			return array();
		}
		try {
			return \WordPress\AiClient\AiClient::defaultRegistry()->getRegisteredProviderIds();
		} catch ( \Throwable $e ) {
			return array();
		}
	}

	/**
	 * Reorders the AI Client registry's private id-to-class map per the saved
	 * priority. Unlisted providers keep their original relative order at the end.
	 *
	 * @return void
	 */
	public function apply_priority_to_registry() {
		$order = $this->get_saved_order();
		if ( empty( $order ) || ! class_exists( \WordPress\AiClient\AiClient::class ) ) {
			return;
		}

		try {
			$registry   = \WordPress\AiClient\AiClient::defaultRegistry();
			$reflection = new \ReflectionClass( $registry );
			if ( ! $reflection->hasProperty( 'registeredIdsToClassNames' ) ) {
				return;
			}
			$prop = $reflection->getProperty( 'registeredIdsToClassNames' );
			$prop->setAccessible( true );

			$current = $prop->getValue( $registry );
			if ( ! is_array( $current ) || empty( $current ) ) {
				return;
			}

			$reordered = array();
			foreach ( $order as $provider_id ) {
				if ( isset( $current[ $provider_id ] ) ) {
					$reordered[ $provider_id ] = $current[ $provider_id ];
				}
			}
			foreach ( $current as $provider_id => $class_name ) {
				if ( ! isset( $reordered[ $provider_id ] ) ) {
					$reordered[ $provider_id ] = $class_name;
				}
			}

			$prop->setValue( $registry, $reordered );
		} catch ( \Throwable $e ) {
			// AiClient internals may have changed — fail silent, defaults still work.
			return;
		}
	}
}
