<?php
/**
 * Fireworks Provider.
 *
 * @package BPluginsAIProviderConnectors
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Provider;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BPlugins\AllInOneAIProviders\Base\BaseProvider;
use BPlugins\AllInOneAIProviders\Metadata\FireworksModelMetadataDirectory;
use BPlugins\AllInOneAIProviders\Models\FireworksTextGenerationModel;

class FireworksProvider extends BaseProvider {
	protected static function config(): array {
		return array(
			'id'             => 'fireworks',
			'name'           => 'Fireworks AI',
			'baseUrl'        => 'https://api.fireworks.ai/inference/v1',
			'credentialsUrl' => 'https://fireworks.ai/account/api-keys',
			'description'    => function_exists( '__' )
				? __( 'Production inference for open models.', 'bplugins-ai-provider-connectors' )
				: 'Production inference for open models.',
			'logoFile'       => 'fireworks.svg',
			'directoryClass' => FireworksModelMetadataDirectory::class,
			'modelClass'     => FireworksTextGenerationModel::class,
		);
	}
}
