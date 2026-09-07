<?php
/**
 * Admin screen: Settings → AI Providers.
 *
 * @package BPluginsAIProviderConnectors
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the management screen and exposes provider "rows" for it and the REST API.
 *
 * @since 1.0.0
 */
class BAIOAP_Admin {

	const MENU_SLUG     = 'baioap';
	const SCRIPT_HANDLE = 'baioap-admin';

	/**
	 * Singleton instance.
	 *
	 * @var BAIOAP_Admin|null
	 */
	private static $instance = null;

	/**
	 * Returns the singleton instance.
	 *
	 * @return BAIOAP_Admin
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
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue' ) );
		add_action( 'wp_ajax_baioap_toggle_videos', array( $this, 'ajax_toggle_videos' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( BAIOAP_FILE ), array( $this, 'action_links' ) );
	}

	/**
	 * Adds the Settings → AI Providers page.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_options_page(
			__( 'AI Providers', 'bplugins-ai-provider-connectors' ),
			__( 'AI Providers', 'bplugins-ai-provider-connectors' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Adds a "Settings" link on the Plugins screen.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public function action_links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( $this->page_url() ) . '">' . esc_html__( 'Settings', 'bplugins-ai-provider-connectors' ) . '</a>' );
		return $links;
	}

	/**
	 * URL of this screen.
	 *
	 * @param string $tab Optional tab.
	 * @return string
	 */
	public function page_url( $tab = '' ) {
		$url = admin_url( 'options-general.php?page=' . self::MENU_SLUG );
		return $tab ? add_query_arg( 'tab', $tab, $url ) : $url;
	}

	/**
	 * URL of core's Connectors screen.
	 *
	 * @return string
	 */
	public function connectors_url() {
		return admin_url( 'options-connectors.php' );
	}

	/**
	 * Enqueues assets on our screen only.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public function maybe_enqueue( $hook_suffix ) {
		if ( 'settings_page_' . self::MENU_SLUG !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style( self::SCRIPT_HANDLE, BAIOAP_URL . 'assets/admin.css', array(), BAIOAP_VERSION );
		wp_enqueue_script( self::SCRIPT_HANDLE, BAIOAP_URL . 'assets/admin.js', array( 'wp-api-fetch', 'wp-i18n' ), BAIOAP_VERSION, true );
		wp_set_script_translations( self::SCRIPT_HANDLE, 'bplugins-ai-provider-connectors' );

		wp_localize_script(
			self::SCRIPT_HANDLE,
			'baioapAdmin',
			array(
				'restBase'      => BAIOAP_REST::REST_NAMESPACE . '/',
				'pageUrl'       => $this->page_url(),
				'connectorsUrl' => $this->connectors_url(),
				'slots'         => BAIOAP_Catalog::CUSTOM_SLOTS,
				'tab'           => $this->current_tab(),
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'videosNonce'   => wp_create_nonce( 'baioap_toggle_videos' ),
				'i18n'          => array(
					'hide' => __( 'Hide videos', 'bplugins-ai-provider-connectors' ),
					'show' => __( 'Show videos', 'bplugins-ai-provider-connectors' ),
				),
			)
		);
	}

	/**
	 * Active tab from the URL.
	 *
	 * @return string
	 */
	private function current_tab() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only navigation state.
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'providers';
		return in_array( $tab, array( 'providers', 'custom', 'priority' ), true ) ? $tab : 'providers';
	}

	/* ---------------------------------------------------------------------
	 * Row data
	 * ------------------------------------------------------------------ */

	/**
	 * Builds the data describing one provider for the UI / REST responses.
	 *
	 * @param string $id Provider ID.
	 * @return array<string, mixed>|null Null when the provider is unknown.
	 */
	public function row( $id ) {
		$custom = BAIOAP_Catalog::custom_by_id( $id );

		if ( $custom ) {
			$row = array(
				'id'             => $id,
				'name'           => $custom['name'],
				'description'    => $custom['description'],
				'logoUrl'        => null,
				'source'         => 'custom',
				'slot'           => (int) $custom['slot'],
				'baseUrl'        => $custom['base_url'],
				'credentialsUrl' => $custom['credentials_url'],
				'enabled'        => BAIOAP_Catalog::is_enabled( $id ),
			);
		} else {
			$class = BAIOAP_Catalog::class_for( $id );
			if ( ! $class ) {
				$class = $this->registered_class( $id );
				if ( ! $class ) {
					return null;
				}
				$source  = 'builtin';
				$enabled = true;
			} else {
				$source  = 'bundled';
				$enabled = BAIOAP_Catalog::is_enabled( $id );
			}

			try {
				$metadata = $class::metadata();
				$row      = array(
					'id'             => $id,
					'name'           => $metadata->getName() ? $metadata->getName() : ucwords( str_replace( array( '-', '_' ), ' ', $id ) ),
					'description'    => (string) $metadata->getDescription(),
					'logoUrl'        => $metadata->getLogoPath() ? $this->logo_url( $metadata->getLogoPath() ) : null,
					'credentialsUrl' => (string) $metadata->getCredentialsUrl(),
				);
			} catch ( \Throwable $e ) {
				$row = array(
					'id'             => $id,
					'name'           => ucwords( str_replace( array( '-', '_' ), ' ', $id ) ),
					'description'    => '',
					'logoUrl'        => null,
					'credentialsUrl' => '',
				);
			}
			$row['source']  = $source;
			$row['enabled'] = $enabled;
		}

		$row['keySource'] = BAIOAP_Catalog::key_source( $id );
		return $row;
	}

	/**
	 * Rows for the bundled providers, in catalog order.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function bundled_rows() {
		$rows = array();
		foreach ( array_keys( BAIOAP_Catalog::bundled() ) as $id ) {
			$row = $this->row( $id );
			if ( $row ) {
				$rows[] = $row;
			}
		}
		return $rows;
	}

	/**
	 * Rows for custom providers, keyed by slot.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function custom_rows() {
		$rows = array();
		foreach ( BAIOAP_Catalog::custom() as $slot => $data ) {
			$row = $this->row( $data['id'] );
			if ( $row ) {
				$rows[ $slot ] = $row;
			}
		}
		return $rows;
	}

	/**
	 * Rows for providers registered by core / other plugins (Anthropic, OpenAI, Google…).
	 *
	 * @return list<array<string, mixed>>
	 */
	public function builtin_rows() {
		$rows = array();
		foreach ( BAIOAP_Priority::instance()->effective_order() as $id ) {
			if ( BAIOAP_Catalog::is_ours( $id ) ) {
				continue;
			}
			$row = $this->row( $id );
			if ( $row ) {
				$rows[] = $row;
			}
		}
		return $rows;
	}

	/**
	 * Rows for every registered provider in effective priority order.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function priority_rows() {
		$rows = array();
		foreach ( BAIOAP_Priority::instance()->effective_order() as $id ) {
			$row = $this->row( $id );
			if ( $row ) {
				$rows[] = $row;
			}
		}
		return $rows;
	}

	/**
	 * Class name of a provider registered with the AI Client.
	 *
	 * @param string $id Provider ID.
	 * @return string|null
	 */
	private function registered_class( $id ) {
		if ( ! class_exists( \WordPress\AiClient\AiClient::class ) ) {
			return null;
		}
		try {
			$registry = \WordPress\AiClient\AiClient::defaultRegistry();
			return $registry->hasProvider( $id ) ? $registry->getProviderClassName( $id ) : null;
		} catch ( \Throwable $e ) {
			return null;
		}
	}

	/**
	 * Converts an absolute logo file path to a URL inside wp-content.
	 *
	 * @param string $path Absolute file path.
	 * @return string|null URL or null when the file is outside the plugin directories.
	 */
	private function logo_url( $path ) {
		$path    = wp_normalize_path( $path );
		$plugins = wp_normalize_path( WP_PLUGIN_DIR );
		if ( 0 === strpos( $path, $plugins . '/' ) ) {
			return plugins_url( substr( $path, strlen( $plugins ) ) );
		}
		$mu_plugins = defined( 'WPMU_PLUGIN_DIR' ) ? wp_normalize_path( WPMU_PLUGIN_DIR ) : '';
		if ( $mu_plugins && 0 === strpos( $path, $mu_plugins . '/' ) ) {
			return plugins_url( substr( $path, strlen( $mu_plugins ) ), WPMU_PLUGIN_DIR . '/.' );
		}
		return null;
	}

	/* ---------------------------------------------------------------------
	 * Rendering
	 * ------------------------------------------------------------------ */

	/**
	 * Renders the screen.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$tab      = $this->current_tab();
		$bundled  = $this->bundled_rows();
		$custom   = $this->custom_rows();
		$builtin  = $this->builtin_rows();
		$priority = $this->priority_rows();

		$managed       = array_merge( $bundled, array_values( $custom ) );
		$enabled_count = count( array_filter( $managed, static function ( $r ) { return $r['enabled']; } ) );
		$keyed_count   = count( array_filter( array_merge( $managed, $builtin ), static function ( $r ) { return 'none' !== $r['keySource']; } ) );

		$tabs = array(
			'providers' => array( __( 'Providers', 'bplugins-ai-provider-connectors' ), 'dashicons-cloud' ),
			'custom'    => array( __( 'Custom providers', 'bplugins-ai-provider-connectors' ), 'dashicons-admin-links' ),
			'priority'  => array( __( 'Priority', 'bplugins-ai-provider-connectors' ), 'dashicons-sort' ),
		);
		?>
		<div class="wrap baioap">
			<h1 class="screen-reader-text"><?php esc_html_e( 'AI Providers', 'bplugins-ai-provider-connectors' ); ?></h1>

			<header class="baioap-header">
				<div class="baioap-header__brand">
					<img class="baioap-logo" src="<?php echo esc_url( BAIOAP_URL . 'assets/icon.svg' ); ?>" width="44" height="44" alt="" aria-hidden="true" />
					<div>
						<div class="baioap-header__title"><?php esc_html_e( 'AI Provider Connectors', 'bplugins-ai-provider-connectors' ); ?> <span class="baioap-pill baioap-pill--muted">v<?php echo esc_html( BAIOAP_VERSION ); ?></span></div>
						<div class="baioap-header__sub"><?php esc_html_e( 'Switch on the providers you want, connect each one with an API key, and decide which provider WordPress tries first.', 'bplugins-ai-provider-connectors' ); ?></div>
					</div>
				</div>
				<div class="baioap-header__actions">
					<a class="baioap-btn baioap-btn--ghost" href="<?php echo esc_url( $this->connectors_url() ); ?>"><span class="dashicons dashicons-admin-network"></span> <?php esc_html_e( 'Manage API keys', 'bplugins-ai-provider-connectors' ); ?></a>
					<a class="baioap-btn baioap-btn--ghost" href="https://wordpress.org/support/plugin/bplugins-ai-provider-connectors/" target="_blank" rel="noopener"><span class="dashicons dashicons-sos"></span> <?php esc_html_e( 'Support', 'bplugins-ai-provider-connectors' ); ?></a>
				</div>
			</header>

			<div id="baioap-notices"></div>

			<div class="baioap-layout">
				<nav class="baioap-nav baioap-tabs" aria-label="<?php esc_attr_e( 'AI Providers sections', 'bplugins-ai-provider-connectors' ); ?>">
					<div class="baioap-nav__brand">
						<img class="baioap-logo baioap-logo--sm" src="<?php echo esc_url( BAIOAP_URL . 'assets/icon.svg' ); ?>" width="28" height="28" alt="" aria-hidden="true" />
						<span><?php esc_html_e( 'AI Providers', 'bplugins-ai-provider-connectors' ); ?></span>
					</div>
					<div class="baioap-nav__group"><?php esc_html_e( 'Sections', 'bplugins-ai-provider-connectors' ); ?></div>
					<?php foreach ( $tabs as $key => $meta ) : ?>
						<a href="<?php echo esc_url( $this->page_url( $key ) ); ?>" class="nav-tab baioap-nav__item<?php echo $tab === $key ? ' nav-tab-active' : ''; ?>" data-tab="<?php echo esc_attr( $key ); ?>" <?php echo $tab === $key ? 'aria-current="page"' : ''; ?>>
							<span class="dashicons <?php echo esc_attr( $meta[1] ); ?>"></span>
							<span class="baioap-nav__label"><?php echo esc_html( $meta[0] ); ?></span>
							<?php if ( 'custom' === $key ) : ?>
								<span class="baioap-nav__count" data-stat="custom"><?php echo (int) count( $custom ); ?></span>
							<?php endif; ?>
						</a>
					<?php endforeach; ?>

					<div class="baioap-nav__group"><?php esc_html_e( 'At a glance', 'bplugins-ai-provider-connectors' ); ?></div>
					<div class="baioap-stats" id="baioap-stats">
						<span class="baioap-stat"><strong data-stat="enabled"><?php echo (int) $enabled_count; ?></strong> <?php esc_html_e( 'enabled', 'bplugins-ai-provider-connectors' ); ?></span>
						<span class="baioap-stat"><strong data-stat="keyed"><?php echo (int) $keyed_count; ?></strong> <?php esc_html_e( 'with an API key', 'bplugins-ai-provider-connectors' ); ?></span>
						<span class="baioap-stat"><strong><?php echo (int) count( $custom ); ?></strong> / <?php echo (int) BAIOAP_Catalog::CUSTOM_SLOTS; ?> <?php esc_html_e( 'custom', 'bplugins-ai-provider-connectors' ); ?></span>
					</div>
				</nav>

				<main class="baioap-content">

					<section class="baioap-panel baioap-tab" data-tab="providers" <?php echo 'providers' === $tab ? '' : 'hidden'; ?>>
						<?php $this->render_videos(); ?>

						<div class="baioap-panel__head">
							<div class="baioap-panel__title">
								<span class="baioap-panel__icon"><span class="dashicons dashicons-cloud"></span></span>
								<div>
									<h2><?php esc_html_e( 'Providers', 'bplugins-ai-provider-connectors' ); ?></h2>
									<p><?php esc_html_e( 'Disabled providers are hidden from the Connectors screen and never used by AI features. Keys you have already saved are kept.', 'bplugins-ai-provider-connectors' ); ?></p>
								</div>
							</div>
						</div>

						<div class="baioap-grid" id="baioap-bundled">
							<?php foreach ( $bundled as $row ) { $this->render_card( $row ); } ?>
						</div>

						<?php if ( $builtin ) : ?>
							<div class="baioap-group">
								<div class="baioap-group__head">
									<h3><?php esc_html_e( 'Built-in providers', 'bplugins-ai-provider-connectors' ); ?></h3>
									<p><?php esc_html_e( 'Registered by WordPress or other plugins. Shown here so you can test them and see their status in one place.', 'bplugins-ai-provider-connectors' ); ?></p>
								</div>
							</div>
							<div class="baioap-grid" id="baioap-builtin">
								<?php foreach ( $builtin as $row ) { $this->render_card( $row ); } ?>
							</div>
						<?php endif; ?>
					</section>

					<section class="baioap-panel baioap-tab" data-tab="custom" <?php echo 'custom' === $tab ? '' : 'hidden'; ?>>
						<div class="baioap-panel__head">
							<div class="baioap-panel__title">
								<span class="baioap-panel__icon"><span class="dashicons dashicons-admin-links"></span></span>
								<div>
									<h2><?php esc_html_e( 'Custom providers', 'bplugins-ai-provider-connectors' ); ?></h2>
									<p><?php esc_html_e( 'Connect any OpenAI-compatible API — a hosted service, or a local server such as Ollama, LM Studio or vLLM. After adding it, save its API key on the Connectors screen.', 'bplugins-ai-provider-connectors' ); ?></p>
								</div>
							</div>
							<div class="baioap-toolbar__actions">
								<span class="baioap-slots" id="baioap-slots"><?php echo esc_html( sprintf( /* translators: 1: used slots, 2: total slots. */ __( '%1$d of %2$d used', 'bplugins-ai-provider-connectors' ), count( $custom ), BAIOAP_Catalog::CUSTOM_SLOTS ) ); ?></span>
								<button type="button" class="button button-primary baioap-btn baioap-btn--primary" id="baioap-add-custom" <?php disabled( count( $custom ) >= BAIOAP_Catalog::CUSTOM_SLOTS ); ?>><?php esc_html_e( 'Add provider', 'bplugins-ai-provider-connectors' ); ?></button>
							</div>
						</div>

						<div class="baioap-formpanel" id="baioap-custom-panel" hidden>
							<form id="baioap-custom-form" class="baioap-form" novalidate>
								<h2 class="baioap-formpanel__title baioap-field--full" data-role="panel-title"><?php esc_html_e( 'Add a custom provider', 'bplugins-ai-provider-connectors' ); ?></h2>
								<div class="baioap-field">
									<label for="baioap-f-name"><?php esc_html_e( 'Name', 'bplugins-ai-provider-connectors' ); ?></label>
									<input type="text" id="baioap-f-name" name="name" maxlength="60" required autocomplete="off" placeholder="<?php esc_attr_e( 'e.g. Ollama (local)', 'bplugins-ai-provider-connectors' ); ?>" />
									<p class="baioap-field__error" data-role="error"></p>
								</div>
								<div class="baioap-field">
									<label for="baioap-f-id"><?php esc_html_e( 'ID', 'bplugins-ai-provider-connectors' ); ?></label>
									<input type="text" id="baioap-f-id" name="id" maxlength="40" required autocomplete="off" pattern="[a-z0-9][a-z0-9_-]{1,39}" />
									<p class="description"><?php esc_html_e( 'Lowercase letters, numbers, hyphens. Cannot be changed later — API keys are stored under it.', 'bplugins-ai-provider-connectors' ); ?></p>
									<p class="baioap-field__error" data-role="error"></p>
								</div>
								<div class="baioap-field baioap-field--full">
									<label for="baioap-f-base-url"><?php esc_html_e( 'Base URL', 'bplugins-ai-provider-connectors' ); ?></label>
									<input type="url" id="baioap-f-base-url" name="base_url" required autocomplete="off" placeholder="https://api.example.com/v1" />
									<p class="description"><?php esc_html_e( 'The root of the OpenAI-compatible API. The plugin calls {base URL}/models and {base URL}/chat/completions.', 'bplugins-ai-provider-connectors' ); ?></p>
									<p class="baioap-field__error" data-role="error"></p>
								</div>
								<div class="baioap-field">
									<label for="baioap-f-credentials-url"><?php esc_html_e( 'Where to get an API key (optional)', 'bplugins-ai-provider-connectors' ); ?></label>
									<input type="url" id="baioap-f-credentials-url" name="credentials_url" autocomplete="off" placeholder="https://" />
									<p class="baioap-field__error" data-role="error"></p>
								</div>
								<div class="baioap-field">
									<label for="baioap-f-description"><?php esc_html_e( 'Description (optional)', 'bplugins-ai-provider-connectors' ); ?></label>
									<input type="text" id="baioap-f-description" name="description" maxlength="160" autocomplete="off" />
									<p class="baioap-field__error" data-role="error"></p>
								</div>
								<div class="baioap-form__actions">
									<button type="submit" class="button button-primary baioap-btn baioap-btn--primary" data-role="submit"><?php esc_html_e( 'Save provider', 'bplugins-ai-provider-connectors' ); ?></button>
									<button type="button" class="button baioap-btn" data-role="cancel"><?php esc_html_e( 'Cancel', 'bplugins-ai-provider-connectors' ); ?></button>
									<span class="spinner"></span>
								</div>
							</form>
						</div>

						<div class="baioap-grid" id="baioap-custom">
							<?php foreach ( $custom as $row ) { $this->render_card( $row ); } ?>
						</div>
						<div class="baioap-empty" id="baioap-custom-empty" <?php echo $custom ? 'hidden' : ''; ?>>
							<span class="dashicons dashicons-cloud" aria-hidden="true"></span>
							<h3><?php esc_html_e( 'No custom providers yet.', 'bplugins-ai-provider-connectors' ); ?></h3>
							<p><?php esc_html_e( 'Add one to use a provider that is not bundled, or a model server running on your own hardware.', 'bplugins-ai-provider-connectors' ); ?></p>
						</div>
					</section>

					<section class="baioap-panel baioap-tab" data-tab="priority" <?php echo 'priority' === $tab ? '' : 'hidden'; ?>>
						<div class="baioap-panel__head">
							<div class="baioap-panel__title">
								<span class="baioap-panel__icon"><span class="dashicons dashicons-sort"></span></span>
								<div>
									<h2><?php esc_html_e( 'Priority', 'bplugins-ai-provider-connectors' ); ?></h2>
									<p><?php esc_html_e( 'When a feature needs an AI provider, WordPress walks this list from the top and uses the first connected provider that supports the task. Drag to reorder, or use the arrows.', 'bplugins-ai-provider-connectors' ); ?></p>
								</div>
							</div>
						</div>
						<?php if ( empty( $priority ) ) : ?>
							<div class="baioap-empty"><p><?php esc_html_e( 'No AI providers are registered yet.', 'bplugins-ai-provider-connectors' ); ?></p></div>
						<?php else : ?>
							<div class="baioap-group">
								<ol class="baioap-priority" id="baioap-priority">
									<?php foreach ( $priority as $index => $row ) { $this->render_priority_item( $row, $index + 1 ); } ?>
								</ol>
								<div class="baioap-priority__actions">
									<button type="button" class="button button-primary baioap-btn baioap-btn--primary" id="baioap-priority-save" disabled><?php esc_html_e( 'Save order', 'bplugins-ai-provider-connectors' ); ?></button>
									<button type="button" class="button baioap-btn" id="baioap-priority-reset" <?php disabled( empty( BAIOAP_Priority::instance()->get_saved_order() ) ); ?>><?php esc_html_e( 'Reset to default', 'bplugins-ai-provider-connectors' ); ?></button>
									<span class="baioap-priority__hint" id="baioap-priority-hint"></span>
								</div>
							</div>
						<?php endif; ?>
					</section>
				</main>
			</div>
		</div>
		<?php
	}

	/**
	 * Whether this user has collapsed the video strip.
	 *
	 * Stored per user and rendered server side so the panel never flashes
	 * open before the script runs.
	 *
	 * @return bool
	 */
	public function videos_hidden() {
		return (bool) get_user_meta( get_current_user_id(), 'baioap_videos_hidden', true );
	}

	/**
	 * Remembers the open or closed state of the video strip.
	 *
	 * @return void
	 */
	public function ajax_toggle_videos() {
		check_ajax_referer( 'baioap_toggle_videos' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Not allowed.', 'bplugins-ai-provider-connectors' ) ), 403 );
		}

		$hidden = empty( $_POST['hidden'] ) ? 0 : 1; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked above.
		update_user_meta( get_current_user_id(), 'baioap_videos_hidden', $hidden );

		wp_send_json_success( array( 'hidden' => $hidden ) );
	}

	/**
	 * Renders the "Watch and learn" strip with two click-to-load YouTube videos.
	 *
	 * @return void
	 */
	private function render_videos() {
		$hidden = $this->videos_hidden();
		$videos = array(
			array(
				'id'     => BAIOAP_INTRO_VIDEO,
				'title'  => __( 'See what the plugin does', 'bplugins-ai-provider-connectors' ),
				'copy'   => __( 'A quick look at the bundled providers, custom OpenAI-compatible endpoints and the priority list. The full step-by-step tutorial is coming soon.', 'bplugins-ai-provider-connectors' ),
				'label'  => __( 'Play the video (opens YouTube in this page)', 'bplugins-ai-provider-connectors' ),
				'length' => __( 'Teaser', 'bplugins-ai-provider-connectors' ),
			),
		);
		?>
		<div class="baioap-videos<?php echo $hidden ? ' is-collapsed' : ''; ?>" data-baioap-videos>
			<div class="baioap-videos__bar">
				<div class="baioap-videos__label">
					<span class="dashicons dashicons-video-alt3" aria-hidden="true"></span>
					<span><?php esc_html_e( 'Watch and learn', 'bplugins-ai-provider-connectors' ); ?></span>
					<span class="baioap-pill baioap-pill--soon"><?php esc_html_e( 'Start here', 'bplugins-ai-provider-connectors' ); ?></span>
				</div>
				<button type="button" class="baioap-videos__toggle" data-baioap-videos-toggle
					aria-expanded="<?php echo $hidden ? 'false' : 'true'; ?>" aria-controls="baioap-videos-panel">
					<span class="baioap-videos__chev" aria-hidden="true"></span>
					<span data-baioap-videos-label><?php echo $hidden ? esc_html__( 'Show videos', 'bplugins-ai-provider-connectors' ) : esc_html__( 'Hide videos', 'bplugins-ai-provider-connectors' ); ?></span>
				</button>
			</div>

			<div class="baioap-videos__grid" id="baioap-videos-panel"<?php echo $hidden ? ' hidden' : ''; ?>>
				<?php foreach ( $videos as $video ) : ?>
					<article class="baioap-vid">
						<div class="baioap-vid__player">
							<button type="button" class="baioap-vid__facade" data-baioap-video="<?php echo esc_attr( $video['id'] ); ?>">
								<img src="<?php echo esc_url( 'https://i.ytimg.com/vi/' . rawurlencode( $video['id'] ) . '/hqdefault.jpg' ); ?>" alt="" loading="lazy" />
								<span class="baioap-vid__play" aria-hidden="true"></span>
								<span class="baioap-vid__length"><?php echo esc_html( $video['length'] ); ?></span>
								<span class="screen-reader-text"><?php echo esc_html( $video['label'] ); ?></span>
							</button>
						</div>
						<div class="baioap-vid__body">
							<h3><?php echo esc_html( $video['title'] ); ?></h3>
							<p><?php echo esc_html( $video['copy'] ); ?></p>
							<a class="baioap-link" href="<?php echo esc_url( 'https://www.youtube.com/watch?v=' . $video['id'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Watch on YouTube', 'bplugins-ai-provider-connectors' ); ?> &rarr;</a>
						</div>
					</article>
				<?php endforeach; ?>
			</div>

			<p class="baioap-videos__note"<?php echo $hidden ? ' hidden' : ''; ?>><?php esc_html_e( 'The player is not loaded from YouTube until you press play.', 'bplugins-ai-provider-connectors' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Renders one provider card.
	 *
	 * @param array $row Row data from row().
	 * @return void
	 */
	private function render_card( array $row ) {
		$is_ours  = 'builtin' !== $row['source'];
		$enabled  = ! empty( $row['enabled'] );
		$has_key  = 'none' !== $row['keySource'];
		$initials = strtoupper( mb_substr( trim( (string) $row['name'] ), 0, 2 ) );

		if ( ! $enabled ) {
			$badge = array( 'muted', __( 'Disabled', 'bplugins-ai-provider-connectors' ) );
		} elseif ( ! $has_key ) {
			$badge = array( 'idle', __( 'No API key', 'bplugins-ai-provider-connectors' ) );
		} else {
			$badge = array( 'info', __( 'Key set', 'bplugins-ai-provider-connectors' ) );
		}
		?>
		<article class="baioap-card<?php echo $enabled ? '' : ' is-disabled'; ?>"
			data-id="<?php echo esc_attr( $row['id'] ); ?>"
			data-source="<?php echo esc_attr( $row['source'] ); ?>"
			data-enabled="<?php echo $enabled ? '1' : '0'; ?>"
			data-key-source="<?php echo esc_attr( $row['keySource'] ); ?>"
			data-name="<?php echo esc_attr( $row['name'] ); ?>"
			<?php if ( 'custom' === $row['source'] ) : ?>
				data-slot="<?php echo (int) $row['slot']; ?>"
				data-base-url="<?php echo esc_attr( $row['baseUrl'] ); ?>"
				data-credentials-url="<?php echo esc_attr( $row['credentialsUrl'] ); ?>"
				data-description="<?php echo esc_attr( $row['description'] ); ?>"
			<?php endif; ?>
		>
			<div class="baioap-card__head">
				<?php if ( ! empty( $row['logoUrl'] ) ) : ?>
					<span class="baioap-card__logo"><img src="<?php echo esc_url( $row['logoUrl'] ); ?>" alt="" loading="lazy" /></span>
				<?php else : ?>
					<span class="baioap-card__logo baioap-card__logo--initials" aria-hidden="true"><?php echo esc_html( $initials ); ?></span>
				<?php endif; ?>
				<div class="baioap-card__title">
					<h3>
						<?php echo esc_html( $row['name'] ); ?>
						<?php if ( 'custom' === $row['source'] ) : ?>
							<span class="baioap-chip"><?php esc_html_e( 'Custom', 'bplugins-ai-provider-connectors' ); ?></span>
						<?php elseif ( 'builtin' === $row['source'] ) : ?>
							<span class="baioap-chip"><?php esc_html_e( 'Built-in', 'bplugins-ai-provider-connectors' ); ?></span>
						<?php endif; ?>
					</h3>
				</div>
				<?php if ( $is_ours ) : ?>
					<label class="baioap-switch">
						<input type="checkbox" role="switch" class="baioap-switch__input" <?php checked( $enabled ); ?>
							aria-label="<?php echo esc_attr( sprintf( /* translators: %s: provider name. */ __( 'Enable %s', 'bplugins-ai-provider-connectors' ), $row['name'] ) ); ?>" />
						<span class="baioap-switch__track" aria-hidden="true"></span>
					</label>
				<?php endif; ?>
			</div>

			<p class="baioap-card__desc"><?php echo esc_html( $row['description'] ); ?></p>
			<?php if ( 'custom' === $row['source'] ) : ?>
				<p class="baioap-card__url"><code><?php echo esc_html( $row['baseUrl'] ); ?></code></p>
			<?php endif; ?>

			<div class="baioap-card__status">
				<span class="baioap-badge is-<?php echo esc_attr( $badge[0] ); ?>" data-role="badge"><?php echo esc_html( $badge[1] ); ?></span>
				<span class="baioap-card__message" data-role="message" aria-live="polite"></span>
				<?php if ( 'custom' !== $row['source'] && ! empty( $row['credentialsUrl'] ) ) : ?>
					<a class="baioap-card__link" href="<?php echo esc_url( $row['credentialsUrl'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Get an API key', 'bplugins-ai-provider-connectors' ); ?> <span aria-hidden="true">&#8599;</span></a>
				<?php endif; ?>
			</div>

			<div class="baioap-card__actions">
				<button type="button" class="button baioap-test" <?php disabled( ! $enabled ); ?>><?php esc_html_e( 'Test connection', 'bplugins-ai-provider-connectors' ); ?></button>
				<a class="button" href="<?php echo esc_url( $this->connectors_url() ); ?>"><?php echo $has_key ? esc_html__( 'Manage key', 'bplugins-ai-provider-connectors' ) : esc_html__( 'Add key', 'bplugins-ai-provider-connectors' ); ?></a>
				<?php if ( 'custom' === $row['source'] ) : ?>
					<span class="baioap-card__links">
						<button type="button" class="button-link baioap-edit"><?php esc_html_e( 'Edit', 'bplugins-ai-provider-connectors' ); ?></button>
						<button type="button" class="button-link button-link-delete baioap-delete"><?php esc_html_e( 'Delete', 'bplugins-ai-provider-connectors' ); ?></button>
					</span>
				<?php endif; ?>
			</div>
		</article>
		<?php
	}

	/**
	 * Renders one priority list item.
	 *
	 * @param array $row  Row data.
	 * @param int   $rank 1-based position.
	 * @return void
	 */
	private function render_priority_item( array $row, $rank ) {
		$has_key = 'none' !== $row['keySource'];
		?>
		<li class="baioap-priority__item" draggable="true" data-id="<?php echo esc_attr( $row['id'] ); ?>">
			<span class="baioap-priority__handle" aria-hidden="true">&#8942;&#8942;</span>
			<span class="baioap-priority__rank" data-role="rank"><?php echo (int) $rank; ?></span>
			<?php if ( ! empty( $row['logoUrl'] ) ) : ?>
				<img class="baioap-priority__logo" src="<?php echo esc_url( $row['logoUrl'] ); ?>" alt="" />
			<?php else : ?>
				<span class="baioap-priority__logo baioap-priority__logo--placeholder" aria-hidden="true"></span>
			<?php endif; ?>
			<span class="baioap-priority__name"><?php echo esc_html( $row['name'] ); ?>
				<?php if ( 'custom' === $row['source'] ) : ?><span class="baioap-chip"><?php esc_html_e( 'Custom', 'bplugins-ai-provider-connectors' ); ?></span><?php elseif ( 'builtin' === $row['source'] ) : ?><span class="baioap-chip"><?php esc_html_e( 'Built-in', 'bplugins-ai-provider-connectors' ); ?></span><?php endif; ?>
			</span>
			<span class="baioap-badge is-<?php echo $has_key ? 'success' : 'idle'; ?>"><?php echo $has_key ? esc_html__( 'Key set', 'bplugins-ai-provider-connectors' ) : esc_html__( 'No API key', 'bplugins-ai-provider-connectors' ); ?></span>
			<span class="baioap-priority__buttons">
				<button type="button" class="button button-small baioap-up" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: provider name. */ __( 'Move %s up', 'bplugins-ai-provider-connectors' ), $row['name'] ) ); ?>">&#9650;</button>
				<button type="button" class="button button-small baioap-down" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: provider name. */ __( 'Move %s down', 'bplugins-ai-provider-connectors' ), $row['name'] ) ); ?>">&#9660;</button>
			</span>
		</li>
		<?php
	}
}
