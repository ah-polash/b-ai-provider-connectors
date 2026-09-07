<?php
/**
 * Plugin Name:       bPlugins AI Provider Connectors
 * Plugin URI:        https://github.com/ah-polash/bplugins-ai-provider-connectors
 * Description:       Connect 11 popular AI providers (OpenRouter, Mistral, Cohere, Groq, xAI, DeepSeek, Perplexity, Together, Fireworks, Hugging Face, Replicate) or any OpenAI-compatible endpoint to the WordPress AI Client, test API keys with one click, and choose which provider is used first.
 * Version:           1.0.0
 * Requires at least: 7.0
 * Requires PHP:      7.4
 * Author:            bPlugins
 * Author URI:        https://bplugins.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       bplugins-ai-provider-connectors
 *
 * @package BPluginsAIProviderConnectors
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BAIOAP_VERSION', '1.0.0' );
define( 'BAIOAP_FILE', __FILE__ );
define( 'BAIOAP_DIR', plugin_dir_path( __FILE__ ) );
define( 'BAIOAP_URL', plugin_dir_url( __FILE__ ) );
// YouTube ID shown on the settings screen (teaser; full tutorial coming soon).
define( 'BAIOAP_INTRO_VIDEO', 'Hfm94aHAbYQ' );
define( 'BAIOAP_TUTORIAL_VIDEO', 'Hfm94aHAbYQ' );

require_once BAIOAP_DIR . 'src/autoload.php';
require_once BAIOAP_DIR . 'includes/class-baioap-catalog.php';
require_once BAIOAP_DIR . 'includes/class-baioap-ai-client.php';
require_once BAIOAP_DIR . 'includes/class-baioap-priority.php';
require_once BAIOAP_DIR . 'includes/class-baioap-rest.php';
require_once BAIOAP_DIR . 'includes/class-baioap-assets.php';
require_once BAIOAP_DIR . 'includes/class-baioap-admin.php';

/**
 * Bootstraps the plugin.
 *
 * @since 1.0.0
 *
 * @return void
 */
function baioap_bootstrap() {
	// Providers register on init:5 so core's connectors init (init:15) can auto-create their connectors.
	BAIOAP_AI_Client::instance()->register_hooks();
	BAIOAP_Priority::instance()->register_hooks();
	BAIOAP_REST::instance()->register_hooks();
	BAIOAP_Assets::instance()->register_hooks();
	BAIOAP_Admin::instance()->register_hooks();
}
add_action( 'plugins_loaded', 'baioap_bootstrap' );
