<?php
/**
 * Asset loading for the B All-in-One AI Providers plugin.
 *
 * @package BAllInOneAIProviders
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueues JS and CSS on the Connectors administration screen.
 *
 * @since 1.0.0
 */
class BAIOAP_Assets {

	const SCRIPT_HANDLE = 'baioap';
	const STYLE_HANDLE  = 'baioap';

	/**
	 * Singleton instance.
	 *
	 * @var BAIOAP_Assets|null
	 */
	private static $instance = null;

	/**
	 * Returns the singleton instance.
	 *
	 * @return BAIOAP_Assets
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
		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue' ) );
	}

	/**
	 * Enqueues assets on the connectors screen only.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 * @return void
	 */
	public function maybe_enqueue( $hook_suffix ) {
		if ( ! $this->is_connectors_screen( $hook_suffix ) ) {
			return;
		}

		wp_enqueue_style(
			self::STYLE_HANDLE,
			BAIOAP_URL . 'assets/baioap.css',
			array(),
			BAIOAP_VERSION
		);

		wp_enqueue_script(
			self::SCRIPT_HANDLE,
			BAIOAP_URL . 'assets/baioap.js',
			array( 'wp-api-fetch', 'wp-i18n' ),
			BAIOAP_VERSION,
			true
		);

		wp_set_script_translations( self::SCRIPT_HANDLE, 'b-all-in-one-ai-providers' );

		wp_localize_script(
			self::SCRIPT_HANDLE,
			'baioapData',
			array(
				'restRoot'   => esc_url_raw( rest_url( BAIOAP_REST::REST_NAMESPACE . '/test/' ) ),
				'nonce'      => wp_create_nonce( 'wp_rest' ),
				'connectors' => $this->get_connector_map(),
			)
		);
	}

	/**
	 * Determines whether the current screen is the Connectors screen.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 * @return bool
	 */
	private function is_connectors_screen( $hook_suffix ) {
		if ( 'settings_page_options-connectors-wp-admin' === $hook_suffix
			|| 'options-connectors.php' === $hook_suffix ) {
			return true;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && 'options-connectors' === $screen->id ) {
			return true;
		}

		return false;
	}

	/**
	 * Builds a name-to-id map of connectors for client-side lookup.
	 *
	 * @return array<int, array{id:string,name:string,type:string,pluginSlug:string,keySource:string}>
	 */
	private function get_connector_map() {
		if ( ! function_exists( 'wp_get_connectors' ) ) {
			return array();
		}

		$map = array();
		foreach ( wp_get_connectors() as $id => $data ) {
			$auth        = isset( $data['authentication'] ) && is_array( $data['authentication'] ) ? $data['authentication'] : array();
			$plugin_file = isset( $data['plugin']['file'] ) ? (string) $data['plugin']['file'] : '';
			$plugin_slug = '';
			if ( '' !== $plugin_file ) {
				$plugin_slug = strpos( $plugin_file, '/' ) !== false
					? strstr( $plugin_file, '/', true )
					: preg_replace( '/\.php$/', '', $plugin_file );
			}

			$key_source = 'none';
			if ( function_exists( '_wp_connectors_get_api_key_source' ) && ! empty( $auth['setting_name'] ) ) {
				$key_source = _wp_connectors_get_api_key_source(
					$auth['setting_name'],
					isset( $auth['env_var_name'] ) ? $auth['env_var_name'] : '',
					isset( $auth['constant_name'] ) ? $auth['constant_name'] : ''
				);
			}

			$map[] = array(
				'id'         => (string) $id,
				'name'       => (string) $data['name'],
				'type'       => isset( $data['type'] ) ? (string) $data['type'] : '',
				'pluginSlug' => (string) $plugin_slug,
				'keySource'  => (string) $key_source,
			);
		}

		return $map;
	}
}
