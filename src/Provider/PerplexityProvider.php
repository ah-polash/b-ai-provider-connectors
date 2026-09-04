<?php
/**
 * Perplexity Provider.
 *
 * @package BAllInOneAIProviders
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Provider;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BPlugins\AllInOneAIProviders\Base\BaseProvider;
use BPlugins\AllInOneAIProviders\Metadata\PerplexityModelMetadataDirectory;
use BPlugins\AllInOneAIProviders\Models\PerplexityTextGenerationModel;

class PerplexityProvider extends BaseProvider {
	protected static function config(): array {
		return array(
			'id'             => 'perplexity',
			'name'           => 'Perplexity',
			'baseUrl'        => 'https://api.perplexity.ai', // phpcs:ignore PluginCheck.CodeAnalysis.AIProvider.DirectIntegration -- This plugin registers the provider with the WordPress AI Client; the base URL is required for that registration.
			'credentialsUrl' => 'https://www.perplexity.ai/settings/api',
			'description'    => function_exists( '__' )
				? __( 'Online answer-engine models.', 'b-all-in-one-ai-providers' )
				: 'Online answer-engine models.',
			'logoFile'       => 'perplexity.svg',
			'directoryClass' => PerplexityModelMetadataDirectory::class,
			'modelClass'     => PerplexityTextGenerationModel::class,
		);
	}
}
