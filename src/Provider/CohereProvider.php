<?php
/**
 * Cohere Provider.
 *
 * @package BAllInOneAIProviders
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
			'baseUrl'        => 'https://api.cohere.ai/compatibility/v1',
			'credentialsUrl' => 'https://dashboard.cohere.com/api-keys',
			'description'    => function_exists( '__' )
				? __( 'Enterprise AI for retrieval and generation.', 'b-all-in-one-ai-providers' )
				: 'Enterprise AI for retrieval and generation.',
			'logoFile'       => 'cohere.svg',
			'directoryClass' => CohereModelMetadataDirectory::class,
			'modelClass'     => CohereTextGenerationModel::class,
		);
	}
}
