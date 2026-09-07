<?php
/**
 * Mistral Provider.
 *
 * @package BPluginsAIProviderConnectors
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Provider;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BPlugins\AllInOneAIProviders\Base\BaseProvider;
use BPlugins\AllInOneAIProviders\Metadata\MistralModelMetadataDirectory;
use BPlugins\AllInOneAIProviders\Models\MistralTextGenerationModel;

class MistralProvider extends BaseProvider {
	protected static function config(): array {
		return array(
			'id'             => 'mistral',
			'name'           => 'Mistral AI',
			'baseUrl'        => 'https://api.mistral.ai/v1', // phpcs:ignore PluginCheck.CodeAnalysis.AIProvider.DirectIntegration -- This plugin registers the provider with the WordPress AI Client; the base URL is required for that registration.
			'credentialsUrl' => 'https://console.mistral.ai/api-keys/',
			'description'    => function_exists( '__' )
				? __( 'Frontier and open-weight models from Mistral.', 'bplugins-ai-provider-connectors' )
				: 'Frontier and open-weight models from Mistral.',
			'logoFile'       => 'mistral.svg',
			'directoryClass' => MistralModelMetadataDirectory::class,
			'modelClass'     => MistralTextGenerationModel::class,
		);
	}
}
