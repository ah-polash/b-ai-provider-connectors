<?php
/**
 * Together Provider.
 *
 * @package BAIProviderConnectors
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Provider;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BPlugins\AllInOneAIProviders\Base\BaseProvider;
use BPlugins\AllInOneAIProviders\Metadata\TogetherModelMetadataDirectory;
use BPlugins\AllInOneAIProviders\Models\TogetherTextGenerationModel;

class TogetherProvider extends BaseProvider {
	protected static function config(): array {
		return array(
			'id'             => 'together',
			'name'           => 'Together AI',
			'baseUrl'        => 'https://api.together.xyz/v1',
			'credentialsUrl' => 'https://api.together.xyz/settings/api-keys',
			'description'    => function_exists( '__' )
				? __( 'Hosted inference for open-source AI.', 'b-ai-provider-connectors' )
				: 'Hosted inference for open-source AI.',
			'logoFile'       => 'together.svg',
			'directoryClass' => TogetherModelMetadataDirectory::class,
			'modelClass'     => TogetherTextGenerationModel::class,
		);
	}
}
