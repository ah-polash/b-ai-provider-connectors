<?php
/**
 * Open Router Provider.
 *
 * @package BAllInOneAIProviders
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Provider;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BPlugins\AllInOneAIProviders\Base\BaseProvider;
use BPlugins\AllInOneAIProviders\Metadata\OpenRouterModelMetadataDirectory;
use BPlugins\AllInOneAIProviders\Models\OpenRouterTextGenerationModel;

/**
 * OpenRouter provider.
 *
 * @since 1.2.0
 */
class OpenRouterProvider extends BaseProvider {
	protected static function config(): array {
		return array(
			'id'              => 'openrouter',
			'name'            => 'OpenRouter',
			'baseUrl'         => 'https://openrouter.ai/api/v1', // phpcs:ignore PluginCheck.CodeAnalysis.AIProvider.DirectIntegration -- This plugin registers the provider with the WordPress AI Client; the base URL is required for that registration.
			'credentialsUrl'  => 'https://openrouter.ai/keys', // phpcs:ignore PluginCheck.CodeAnalysis.AIProvider.DirectIntegration -- This plugin registers the provider with the WordPress AI Client; the base URL is required for that registration.
			'availabilityUrl' => 'https://openrouter.ai/api/v1/auth/key', // phpcs:ignore PluginCheck.CodeAnalysis.AIProvider.DirectIntegration -- This plugin registers the provider with the WordPress AI Client; the base URL is required for that registration.
			'description'     => function_exists( '__' )
				? __( 'Unified inference for hundreds of AI models.', 'b-all-in-one-ai-providers' )
				: 'Unified inference for hundreds of AI models.',
			'logoFile'        => 'openrouter.svg',
			'directoryClass'  => OpenRouterModelMetadataDirectory::class,
			'modelClass'      => OpenRouterTextGenerationModel::class,
		);
	}
}
