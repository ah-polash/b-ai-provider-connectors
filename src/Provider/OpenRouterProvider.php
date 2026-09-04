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
			'baseUrl'         => 'https://openrouter.ai/api/v1',
			'credentialsUrl'  => 'https://openrouter.ai/keys',
			'availabilityUrl' => 'https://openrouter.ai/api/v1/auth/key',
			'description'     => function_exists( '__' )
				? __( 'Unified inference for hundreds of AI models.', 'b-all-in-one-ai-providers' )
				: 'Unified inference for hundreds of AI models.',
			'logoFile'        => 'openrouter.svg',
			'directoryClass'  => OpenRouterModelMetadataDirectory::class,
			'modelClass'      => OpenRouterTextGenerationModel::class,
		);
	}
}
