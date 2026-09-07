<?php
/**
 * Cohere Provider.
 *
 * @package BAIProviderConnectors
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Provider;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BPlugins\AllInOneAIProviders\Base\BaseProvider;
use BPlugins\AllInOneAIProviders\Metadata\CohereModelMetadataDirectory;
use BPlugins\AllInOneAIProviders\Models\CohereTextGenerationModel;

class CohereProvider extends BaseProvider {
	protected static function config(): array {
		return array(
			'id'             => 'cohere',
			'name'           => 'Cohere',
			'baseUrl'        => 'https://api.cohere.ai/compatibility/v1', // phpcs:ignore PluginCheck.CodeAnalysis.AIProvider.DirectIntegration -- This plugin registers the provider with the WordPress AI Client; the base URL is required for that registration.
			'credentialsUrl' => 'https://dashboard.cohere.com/api-keys',
			'description'    => function_exists( '__' )
				? __( 'Enterprise AI for retrieval and generation.', 'b-ai-provider-connectors' )
				: 'Enterprise AI for retrieval and generation.',
			'logoFile'       => 'cohere.svg',
			'directoryClass' => CohereModelMetadataDirectory::class,
			'modelClass'     => CohereTextGenerationModel::class,
		);
	}
}
