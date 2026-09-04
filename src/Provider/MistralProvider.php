<?php
/**
 * Mistral Provider.
 *
 * @package BAllInOneAIProviders
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
			'baseUrl'        => 'https://api.mistral.ai/v1',
			'credentialsUrl' => 'https://console.mistral.ai/api-keys/',
			'description'    => function_exists( '__' )
				? __( 'Frontier and open-weight models from Mistral.', 'b-all-in-one-ai-providers' )
				: 'Frontier and open-weight models from Mistral.',
			'logoFile'       => 'mistral.svg',
			'directoryClass' => MistralModelMetadataDirectory::class,
			'modelClass'     => MistralTextGenerationModel::class,
		);
	}
}
