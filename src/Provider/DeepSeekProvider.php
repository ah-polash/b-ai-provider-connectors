<?php
/**
 * Deep Seek Provider.
 *
 * @package BPluginsAIProviderConnectors
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Provider;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BPlugins\AllInOneAIProviders\Base\BaseProvider;
use BPlugins\AllInOneAIProviders\Metadata\DeepSeekModelMetadataDirectory;
use BPlugins\AllInOneAIProviders\Models\DeepSeekTextGenerationModel;

class DeepSeekProvider extends BaseProvider {
	protected static function config(): array {
		return array(
			'id'             => 'deepseek',
			'name'           => 'DeepSeek',
			// DeepSeek mounts /chat/completions and /models at the root, not under /v1.
			'baseUrl'        => 'https://api.deepseek.com', // phpcs:ignore PluginCheck.CodeAnalysis.AIProvider.DirectIntegration -- This plugin registers the provider with the WordPress AI Client; the base URL is required for that registration.
			'credentialsUrl' => 'https://platform.deepseek.com/api_keys',
			'description'    => function_exists( '__' )
				? __( 'Reasoning and code models from DeepSeek.', 'bplugins-ai-provider-connectors' )
				: 'Reasoning and code models from DeepSeek.',
			'logoFile'       => 'deepseek.svg',
			'directoryClass' => DeepSeekModelMetadataDirectory::class,
			'modelClass'     => DeepSeekTextGenerationModel::class,
		);
	}
}
