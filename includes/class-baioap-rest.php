<?php
/**
 * REST API: connection tests, enable/disable, custom providers, and priority.
 *
 * @package BAIProviderConnectors
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST routes under `baioap/v1`. All routes require `manage_options`.
 *
 * @since 1.0.0
 */
class BAIOAP_REST {

	const REST_NAMESPACE = 'baioap/v1';

	/**
	 * Singleton instance.
	 *
	 * @var BAIOAP_REST|null
	 */
	private static $instance = null;

	/**
	 * Returns the singleton instance.
	 *
	 * @return BAIOAP_REST
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
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Registers the REST routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		$id_arg = array(
			'type'              => 'string',
			'required'          => true,
			'sanitize_callback' => 'sanitize_key',
		);
		$slot_arg = array(
			'type'     => 'integer',
			'required' => true,
			'minimum'  => 1,
			'maximum'  => BAIOAP_Catalog::CUSTOM_SLOTS,
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/test/(?P<connector_id>[a-z0-9_-]+)',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_test' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array( 'connector_id' => $id_arg ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/providers/(?P<id>[a-z0-9_-]+)',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_toggle' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'id'      => $id_arg,
					'enabled' => array(
						'type'     => 'boolean',
						'required' => true,
					),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/custom',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'handle_custom_list' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'handle_custom_save' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/custom/(?P<slot>[1-9])',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'handle_custom_save' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array( 'slot' => $slot_arg ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'handle_custom_delete' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array( 'slot' => $slot_arg ),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/priority',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'handle_priority_save' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'order' => array(
							'type'     => 'array',
							'required' => true,
							'items'    => array( 'type' => 'string' ),
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'handle_priority_reset' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);
	}

	/**
	 * Permission check for every route.
	 *
	 * @return bool|WP_Error
	 */
	public function check_permission() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You are not allowed to manage AI providers.', 'b-ai-provider-connectors' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	/* ---------------------------------------------------------------------
	 * Connection test
	 * ------------------------------------------------------------------ */

	/**
	 * Re-validates a connector's stored credentials against the provider's live API.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_test( WP_REST_Request $request ) {
		$id = (string) $request['connector_id'];

		if ( ! function_exists( 'wp_get_connector' ) || ! function_exists( '_wp_connectors_is_ai_api_key_valid' ) ) {
			return new WP_Error(
				'baioap_unavailable',
				__( 'The Connectors API is not available on this site.', 'b-ai-provider-connectors' ),
				array( 'status' => 501 )
			);
		}

		$is_ours   = BAIOAP_Catalog::is_ours( $id );
		$connector = wp_get_connector( $id );

		if ( null === $connector && ! $is_ours ) {
			return new WP_Error( 'baioap_unknown_connector', __( 'Unknown connector.', 'b-ai-provider-connectors' ), array( 'status' => 404 ) );
		}

		if ( null !== $connector ) {
			$auth = isset( $connector['authentication'] ) && is_array( $connector['authentication'] ) ? $connector['authentication'] : array();
			if ( empty( $auth['method'] ) || 'api_key' !== $auth['method'] ) {
				return rest_ensure_response( $this->result( false, 'unsupported', __( 'This connector does not use API key authentication.', 'b-ai-provider-connectors' ) ) );
			}
			if ( ! $is_ours && ( ! isset( $connector['type'] ) || 'ai_provider' !== $connector['type'] ) ) {
				// Other connectors: we can confirm a key is present, but cannot reach the provider from here.
				$present = '' !== $this->resolve_api_key( $auth );
				return rest_ensure_response(
					$present
						? $this->result( true, 'key_present', __( 'An API key is configured. Live validation is not available for this connector.', 'b-ai-provider-connectors' ) )
						: $this->result( false, 'missing_key', __( 'No API key is configured for this connector.', 'b-ai-provider-connectors' ) )
				);
			}
			$api_key = $this->resolve_api_key( $auth );
		} else {
			$api_key = BAIOAP_Catalog::key_value( $id );
		}

		if ( '' === $api_key ) {
			return rest_ensure_response( $this->result( false, 'missing_key', __( 'No API key is configured for this connector.', 'b-ai-provider-connectors' ) ) );
		}

		// A provider that is switched off is not in the registry for this request — register it just for the test.
		if ( $is_ours ) {
			BAIOAP_AI_Client::ensure_registered( $id );
		}

		$is_valid = _wp_connectors_is_ai_api_key_valid( $api_key, $id );

		if ( true === $is_valid ) {
			return rest_ensure_response( $this->result( true, 'valid', __( 'Connection successful. The API key is valid.', 'b-ai-provider-connectors' ) ) );
		}
		if ( false === $is_valid ) {
			return rest_ensure_response( $this->result( false, 'invalid', __( 'Connection failed. The API key is not valid.', 'b-ai-provider-connectors' ) ) );
		}
		return rest_ensure_response( $this->result( false, 'unknown', __( 'Unable to verify the connection. Please try again later.', 'b-ai-provider-connectors' ) ) );
	}

	/**
	 * Builds a test result payload.
	 *
	 * @param bool   $success Whether the test passed.
	 * @param string $status  Machine-readable status.
	 * @param string $message Human-readable message.
	 * @return array{success:bool,status:string,message:string}
	 */
	private function result( $success, $status, $message ) {
		return array(
			'success' => (bool) $success,
			'status'  => $status,
			'message' => $message,
		);
	}

	/**
	 * Resolves the API key for a connector, checking env, constant, then option.
	 *
	 * @param array $auth Authentication config from wp_get_connector().
	 * @return string The API key, or '' when missing.
	 */
	private function resolve_api_key( array $auth ) {
		if ( ! empty( $auth['env_var_name'] ) ) {
			$env_value = getenv( $auth['env_var_name'] );
			if ( false !== $env_value && '' !== $env_value ) {
				return (string) $env_value;
			}
		}
		if ( ! empty( $auth['constant_name'] ) && defined( $auth['constant_name'] ) ) {
			$const_value = constant( $auth['constant_name'] );
			if ( is_string( $const_value ) && '' !== $const_value ) {
				return $const_value;
			}
		}
		if ( ! empty( $auth['setting_name'] ) ) {
			$db_value = get_option( $auth['setting_name'], '' );
			if ( is_string( $db_value ) && '' !== $db_value ) {
				return $db_value;
			}
		}
		return '';
	}

	/* ---------------------------------------------------------------------
	 * Enable / disable
	 * ------------------------------------------------------------------ */

	/**
	 * Enables or disables one of our providers.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_toggle( WP_REST_Request $request ) {
		$id = (string) $request['id'];
		if ( ! BAIOAP_Catalog::is_ours( $id ) ) {
			return new WP_Error( 'baioap_unknown_provider', __( 'Unknown provider.', 'b-ai-provider-connectors' ), array( 'status' => 404 ) );
		}
		$enabled = (bool) $request['enabled'];
		BAIOAP_Catalog::set_enabled( $id, $enabled );

		return rest_ensure_response(
			array(
				'id'      => $id,
				'enabled' => $enabled,
				'row'     => BAIOAP_Admin::instance()->row( $id ),
			)
		);
	}

	/* ---------------------------------------------------------------------
	 * Custom providers
	 * ------------------------------------------------------------------ */

	/**
	 * Lists custom providers.
	 *
	 * @return WP_REST_Response
	 */
	public function handle_custom_list() {
		return rest_ensure_response(
			array(
				'slots' => BAIOAP_Catalog::CUSTOM_SLOTS,
				'items' => array_values( BAIOAP_Admin::instance()->custom_rows() ),
			)
		);
	}

	/**
	 * Creates (POST /custom) or updates (PUT /custom/{slot}) a custom provider.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_custom_save( WP_REST_Request $request ) {
		$slot  = $request->get_param( 'slot' );
		$slot  = null === $slot ? null : (int) $slot;
		$input = $request->get_json_params();
		if ( ! is_array( $input ) ) {
			$input = $request->get_body_params();
		}

		if ( null !== $slot ) {
			$custom = BAIOAP_Catalog::custom();
			if ( ! isset( $custom[ $slot ] ) ) {
				return new WP_Error( 'baioap_unknown_slot', __( 'That custom provider no longer exists.', 'b-ai-provider-connectors' ), array( 'status' => 404 ) );
			}
		}

		$data = BAIOAP_Catalog::validate_custom( is_array( $input ) ? $input : array(), $slot );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$saved_slot = BAIOAP_Catalog::save_custom( $data, $slot );
		if ( is_wp_error( $saved_slot ) ) {
			return $saved_slot;
		}

		return rest_ensure_response(
			array(
				'slot' => $saved_slot,
				'row'  => BAIOAP_Admin::instance()->row( $data['id'] ),
			)
		);
	}

	/**
	 * Deletes a custom provider.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_custom_delete( WP_REST_Request $request ) {
		$slot = (int) $request['slot'];
		if ( ! BAIOAP_Catalog::delete_custom( $slot ) ) {
			return new WP_Error( 'baioap_unknown_slot', __( 'That custom provider no longer exists.', 'b-ai-provider-connectors' ), array( 'status' => 404 ) );
		}
		return rest_ensure_response( array( 'deleted' => true ) );
	}

	/* ---------------------------------------------------------------------
	 * Priority
	 * ------------------------------------------------------------------ */

	/**
	 * Saves the provider priority order.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response
	 */
	public function handle_priority_save( WP_REST_Request $request ) {
		$order = BAIOAP_Priority::instance()->save_order( (array) $request['order'] );
		return rest_ensure_response( array( 'order' => $order ) );
	}

	/**
	 * Resets the provider priority order.
	 *
	 * @return WP_REST_Response
	 */
	public function handle_priority_reset() {
		BAIOAP_Priority::instance()->reset_order();
		return rest_ensure_response( array( 'order' => array() ) );
	}
}
