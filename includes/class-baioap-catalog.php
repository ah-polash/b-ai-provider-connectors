<?php
/**
 * Provider catalog: bundled providers, enable/disable state, custom providers, and API key helpers.
 *
 * @package BPluginsAIProviderConnectors
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static catalog of everything the plugin knows about providers.
 *
 * @since 1.0.0
 */
class BAIOAP_Catalog {

	const OPTION_DISABLED = 'baioap_disabled_providers';
	const OPTION_CUSTOM   = 'baioap_custom_providers';
	const CUSTOM_SLOTS    = 5;

	/**
	 * Bundled providers, in default registration order.
	 *
	 * @return array<string, class-string> Provider ID => provider class.
	 */
	public static function bundled() {
		return array(
			'openrouter'  => \BPlugins\AllInOneAIProviders\Provider\OpenRouterProvider::class,
			'mistral'     => \BPlugins\AllInOneAIProviders\Provider\MistralProvider::class,
			'cohere'      => \BPlugins\AllInOneAIProviders\Provider\CohereProvider::class,
			'groq'        => \BPlugins\AllInOneAIProviders\Provider\GroqProvider::class,
			'xai'         => \BPlugins\AllInOneAIProviders\Provider\XAIProvider::class,
			'deepseek'    => \BPlugins\AllInOneAIProviders\Provider\DeepSeekProvider::class,
			'perplexity'  => \BPlugins\AllInOneAIProviders\Provider\PerplexityProvider::class,
			'together'    => \BPlugins\AllInOneAIProviders\Provider\TogetherProvider::class,
			'fireworks'   => \BPlugins\AllInOneAIProviders\Provider\FireworksProvider::class,
			'huggingface' => \BPlugins\AllInOneAIProviders\Provider\HuggingFaceProvider::class,
			'replicate'   => \BPlugins\AllInOneAIProviders\Provider\ReplicateProvider::class,
		);
	}

	/**
	 * Returns true when the ID belongs to a bundled provider.
	 *
	 * @param string $id Provider ID.
	 * @return bool
	 */
	public static function is_bundled( $id ) {
		$bundled = self::bundled();
		return isset( $bundled[ $id ] );
	}

	/**
	 * Returns true when the ID belongs to a bundled or custom provider (i.e. one we manage).
	 *
	 * @param string $id Provider ID.
	 * @return bool
	 */
	public static function is_ours( $id ) {
		return self::is_bundled( $id ) || null !== self::custom_by_id( $id );
	}

	/**
	 * Resolves the provider class for a bundled or custom provider ID.
	 *
	 * @param string $id Provider ID.
	 * @return string|null Class name, or null when unknown.
	 */
	public static function class_for( $id ) {
		$bundled = self::bundled();
		if ( isset( $bundled[ $id ] ) ) {
			return $bundled[ $id ];
		}
		$custom = self::custom_by_id( $id );
		return $custom ? self::custom_class( (int) $custom['slot'] ) : null;
	}

	/* ---------------------------------------------------------------------
	 * Enable / disable
	 * ------------------------------------------------------------------ */

	/**
	 * IDs of providers the site owner has switched off.
	 *
	 * @return list<string>
	 */
	public static function disabled() {
		$saved = get_option( self::OPTION_DISABLED, array() );
		if ( ! is_array( $saved ) ) {
			return array();
		}
		return array_values( array_filter( array_map( 'sanitize_key', array_filter( $saved, 'is_string' ) ) ) );
	}

	/**
	 * Whether a provider is enabled.
	 *
	 * @param string $id Provider ID.
	 * @return bool
	 */
	public static function is_enabled( $id ) {
		return ! in_array( $id, self::disabled(), true );
	}

	/**
	 * Enables or disables a provider.
	 *
	 * @param string $id      Provider ID.
	 * @param bool   $enabled True to enable.
	 * @return void
	 */
	public static function set_enabled( $id, $enabled ) {
		$disabled = self::disabled();
		if ( $enabled ) {
			$disabled = array_values( array_diff( $disabled, array( $id ) ) );
		} elseif ( ! in_array( $id, $disabled, true ) ) {
			$disabled[] = $id;
		}
		update_option( self::OPTION_DISABLED, $disabled );
	}

	/* ---------------------------------------------------------------------
	 * Custom providers
	 * ------------------------------------------------------------------ */

	/**
	 * Saved custom providers keyed by slot number (1-based).
	 *
	 * @return array<int, array{id:string,name:string,base_url:string,credentials_url:string,description:string}>
	 */
	public static function custom() {
		$saved = get_option( self::OPTION_CUSTOM, array() );
		if ( ! is_array( $saved ) ) {
			return array();
		}
		$out = array();
		foreach ( $saved as $slot => $data ) {
			$slot = (int) $slot;
			if ( $slot < 1 || $slot > self::CUSTOM_SLOTS || ! is_array( $data ) || empty( $data['id'] ) ) {
				continue;
			}
			$out[ $slot ] = array(
				'id'              => sanitize_key( (string) $data['id'] ),
				'name'            => isset( $data['name'] ) ? (string) $data['name'] : '',
				'base_url'        => isset( $data['base_url'] ) ? (string) $data['base_url'] : '',
				'credentials_url' => isset( $data['credentials_url'] ) ? (string) $data['credentials_url'] : '',
				'description'     => isset( $data['description'] ) ? (string) $data['description'] : '',
			);
		}
		ksort( $out );
		return $out;
	}

	/**
	 * Finds a custom provider by ID.
	 *
	 * @param string $id Provider ID.
	 * @return array|null Provider data including 'slot', or null.
	 */
	public static function custom_by_id( $id ) {
		foreach ( self::custom() as $slot => $data ) {
			if ( $data['id'] === $id ) {
				$data['slot'] = $slot;
				return $data;
			}
		}
		return null;
	}

	/**
	 * Provider class for a custom slot.
	 *
	 * @param int $slot Slot number.
	 * @return string
	 */
	public static function custom_class( $slot ) {
		return '\\BPlugins\\AllInOneAIProviders\\Custom\\CustomProvider' . (int) $slot;
	}

	/**
	 * Builds the BaseProvider config for a custom slot. Called from the slot classes.
	 *
	 * @param int    $slot            Slot number.
	 * @param string $directory_class Directory class for the slot.
	 * @param string $model_class     Model class for the slot.
	 * @return array<string, mixed>
	 */
	public static function custom_config( $slot, $directory_class, $model_class ) {
		$custom = self::custom();
		$data   = isset( $custom[ $slot ] ) ? $custom[ $slot ] : array(
			'id'              => 'baioap-custom-' . (int) $slot,
			'name'            => 'Custom ' . (int) $slot,
			'base_url'        => 'https://invalid.invalid/v1',
			'credentials_url' => '',
			'description'     => '',
		);

		return array(
			'id'             => $data['id'],
			'name'           => $data['name'],
			'baseUrl'        => untrailingslashit( $data['base_url'] ),
			'credentialsUrl' => $data['credentials_url'],
			'description'    => $data['description'],
			'logoFile'       => null,
			'directoryClass' => $directory_class,
			'modelClass'     => $model_class,
		);
	}

	/**
	 * IDs that a new custom provider may not use.
	 *
	 * @return list<string>
	 */
	public static function reserved_ids() {
		$ids = array_merge( array_keys( self::bundled() ), array( 'anthropic', 'openai', 'google', 'akismet' ) );
		if ( class_exists( \WordPress\AiClient\AiClient::class ) ) {
			try {
				$ids = array_merge( $ids, \WordPress\AiClient\AiClient::defaultRegistry()->getRegisteredProviderIds() );
			} catch ( \Throwable $e ) {
				// Registry unavailable — fall back to the static list.
				unset( $e );
			}
		}
		return array_values( array_unique( $ids ) );
	}

	/**
	 * Validates and normalises custom provider input.
	 *
	 * @param array    $input Raw input (already unslashed).
	 * @param int|null $slot  Slot being edited, or null when creating.
	 * @return array|WP_Error Clean data or an error.
	 */
	public static function validate_custom( array $input, $slot = null ) {
		$existing = null !== $slot ? self::custom() : array();
		$current  = null !== $slot && isset( $existing[ $slot ] ) ? $existing[ $slot ] : null;

		$name = isset( $input['name'] ) ? sanitize_text_field( (string) $input['name'] ) : '';
		if ( '' === $name || mb_strlen( $name ) > 60 ) {
			return new WP_Error( 'baioap_invalid_name', __( 'Please enter a name of up to 60 characters.', 'bplugins-ai-provider-connectors' ), array( 'status' => 400, 'field' => 'name' ) );
		}

		// The ID is immutable once created because API keys are stored under it.
		if ( $current ) {
			$id = $current['id'];
		} else {
			$id = isset( $input['id'] ) ? sanitize_key( (string) $input['id'] ) : '';
			if ( ! preg_match( '/^[a-z0-9][a-z0-9_-]{1,39}$/', $id ) ) {
				return new WP_Error( 'baioap_invalid_id', __( 'The ID must be 2–40 characters: lowercase letters, numbers, hyphens or underscores.', 'bplugins-ai-provider-connectors' ), array( 'status' => 400, 'field' => 'id' ) );
			}
			$taken = self::reserved_ids();
			foreach ( self::custom() as $data ) {
				$taken[] = $data['id'];
			}
			if ( in_array( $id, $taken, true ) ) {
				return new WP_Error( 'baioap_duplicate_id', __( 'That ID is already used by another provider.', 'bplugins-ai-provider-connectors' ), array( 'status' => 400, 'field' => 'id' ) );
			}
		}

		$base_url = isset( $input['base_url'] ) ? esc_url_raw( trim( (string) $input['base_url'] ), array( 'http', 'https' ) ) : '';
		if ( '' === $base_url || ! wp_http_validate_url( $base_url ) && 0 !== strpos( $base_url, 'http://localhost' ) && 0 !== strpos( $base_url, 'http://127.0.0.1' ) ) {
			return new WP_Error( 'baioap_invalid_url', __( 'Please enter a valid base URL, for example https://api.example.com/v1.', 'bplugins-ai-provider-connectors' ), array( 'status' => 400, 'field' => 'base_url' ) );
		}

		$credentials_url = isset( $input['credentials_url'] ) ? esc_url_raw( trim( (string) $input['credentials_url'] ), array( 'http', 'https' ) ) : '';
		$description     = isset( $input['description'] ) ? sanitize_text_field( (string) $input['description'] ) : '';
		if ( mb_strlen( $description ) > 160 ) {
			$description = mb_substr( $description, 0, 160 );
		}

		return array(
			'id'              => $id,
			'name'            => $name,
			'base_url'        => untrailingslashit( $base_url ),
			'credentials_url' => $credentials_url,
			'description'     => $description,
		);
	}

	/**
	 * Saves a custom provider into a slot (or the first free slot).
	 *
	 * @param array    $data Validated data from validate_custom().
	 * @param int|null $slot Slot to update, or null to create.
	 * @return int|WP_Error Slot number saved, or an error when all slots are full.
	 */
	public static function save_custom( array $data, $slot = null ) {
		$custom = self::custom();
		if ( null === $slot ) {
			for ( $i = 1; $i <= self::CUSTOM_SLOTS; $i++ ) {
				if ( ! isset( $custom[ $i ] ) ) {
					$slot = $i;
					break;
				}
			}
			if ( null === $slot ) {
				return new WP_Error(
					'baioap_slots_full',
					sprintf(
						/* translators: %d: maximum number of custom providers. */
						__( 'You can add up to %d custom providers. Remove one to add another.', 'bplugins-ai-provider-connectors' ),
						self::CUSTOM_SLOTS
					),
					array( 'status' => 400 )
				);
			}
		}
		$custom[ (int) $slot ] = $data;
		ksort( $custom );
		update_option( self::OPTION_CUSTOM, $custom );
		return (int) $slot;
	}

	/**
	 * Deletes a custom provider and its stored API key.
	 *
	 * @param int $slot Slot number.
	 * @return bool True when something was deleted.
	 */
	public static function delete_custom( $slot ) {
		$custom = self::custom();
		if ( ! isset( $custom[ $slot ] ) ) {
			return false;
		}
		$names = self::key_names( $custom[ $slot ]['id'] );
		delete_option( $names['setting'] );
		self::set_enabled( $custom[ $slot ]['id'], true );
		unset( $custom[ $slot ] );
		update_option( self::OPTION_CUSTOM, $custom );
		return true;
	}

	/* ---------------------------------------------------------------------
	 * API key helpers (mirror core's naming in _wp_connectors_register_default_ai_providers)
	 * ------------------------------------------------------------------ */

	/**
	 * Option / constant / environment variable names core uses for a provider's key.
	 *
	 * @param string $id Provider ID.
	 * @return array{setting:string,constant:string,env:string}
	 */
	public static function key_names( $id ) {
		$sanitized = str_replace( '-', '_', $id );
		$constant  = strtoupper( (string) preg_replace( '/([a-z])([A-Z])/', '$1_$2', $sanitized ) ) . '_API_KEY';
		return array(
			'setting'  => "connectors_ai_{$sanitized}_api_key",
			'constant' => $constant,
			'env'      => $constant,
		);
	}

	/**
	 * Where the provider's key comes from: 'none', 'option', 'constant' or 'env'.
	 *
	 * @param string $id Provider ID.
	 * @return string
	 */
	public static function key_source( $id ) {
		$names = self::key_names( $id );
		if ( function_exists( '_wp_connectors_get_api_key_source' ) ) {
			return (string) _wp_connectors_get_api_key_source( $names['setting'], $names['env'], $names['constant'] );
		}
		return '' !== self::key_value( $id ) ? 'option' : 'none';
	}

	/**
	 * Resolves the provider's API key (env var, then constant, then option).
	 *
	 * @param string $id Provider ID.
	 * @return string Key or '' when missing.
	 */
	public static function key_value( $id ) {
		$names = self::key_names( $id );

		$env = getenv( $names['env'] );
		if ( false !== $env && '' !== $env ) {
			return (string) $env;
		}
		if ( defined( $names['constant'] ) ) {
			$const = constant( $names['constant'] );
			if ( is_string( $const ) && '' !== $const ) {
				return $const;
			}
		}
		$option = get_option( $names['setting'], '' );
		return is_string( $option ) ? $option : '';
	}
}
