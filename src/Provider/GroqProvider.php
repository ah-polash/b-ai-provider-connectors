<?php
/**
 * Groq Provider.
 *
 * @package BPluginsAIProviderConnectors
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Provider;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BPlugins\AllInOneAIProviders\Base\BaseProvider;
use BPlugins\AllInOneAIProviders\Metadata\GroqModelMetadataDirectory;
use BPlugins\AllInOneAIProviders\Models\GroqTextGenerationModel;

class GroqProvider extends BaseProvider {
	protected static function config(): array {
		return array(
			'id'             => 'groq',
			'name'           => 'Groq',
			'baseUrl'        => 'https://api.groq.com/openai/v1', // phpcs:ignore PluginCheck.CodeAnalysis.AIProvider.DirectIntegration -- This plugin registers the provider with the WordPress AI Client; the base URL is required for that registration.
			'credentialsUrl' => 'https://console.groq.com/keys',
			'description'    => function_exists( '__' )
				? __( 'Fast inference on open-source models.', 'bplugins-ai-provider-connectors' )
				: 'Fast inference on open-source models.',
			'logoFile'       => 'groq.svg',
			'directoryClass' => GroqModelMetadataDirectory::class,
			'modelClass'     => GroqTextGenerationModel::class,
		);
	}
}
