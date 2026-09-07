<?php
/**
 * Replicate Provider.
 *
 * @package BPluginsAIProviderConnectors
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Provider;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BPlugins\AllInOneAIProviders\Base\BaseProvider;
use BPlugins\AllInOneAIProviders\Metadata\ReplicateModelMetadataDirectory;
use BPlugins\AllInOneAIProviders\Models\ReplicateTextGenerationModel;
use BPlugins\AllInOneAIProviders\Support\ReplicateAvailability;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;

class ReplicateProvider extends BaseProvider {

	protected static function config(): array {
		return array(
			'id'             => 'replicate',
			'name'           => 'Replicate',
			'baseUrl'        => 'https://api.replicate.com/v1',
			'credentialsUrl' => 'https://replicate.com/account/api-tokens',
			'description'    => function_exists( '__' )
				? __( 'Run open-source models in the cloud.', 'bplugins-ai-provider-connectors' )
				: 'Run open-source models in the cloud.',
			'logoFile'       => 'replicate.svg',
			'directoryClass' => ReplicateModelMetadataDirectory::class,
			'modelClass'     => ReplicateTextGenerationModel::class,
		);
	}

	/**
	 * Override the default availability so we don't try to walk /v1/models;
	 * the directory's sendListModelsRequest already hits /v1/account.
	 */
	protected static function createProviderAvailability(): \WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface {
		return new ReplicateAvailability( static::modelMetadataDirectory() );
	}
}
