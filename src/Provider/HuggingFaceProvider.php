<?php
/**
 * Hugging Face Provider.
 *
 * @package BAllInOneAIProviders
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Provider;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BPlugins\AllInOneAIProviders\Base\BaseProvider;
use BPlugins\AllInOneAIProviders\Metadata\HuggingFaceModelMetadataDirectory;
use BPlugins\AllInOneAIProviders\Models\HuggingFaceTextGenerationModel;

class HuggingFaceProvider extends BaseProvider {
	protected static function config(): array {
		return array(
			'id'             => 'huggingface',
			'name'           => 'Hugging Face',
			'baseUrl'        => 'https://router.huggingface.co/v1',
			'credentialsUrl' => 'https://huggingface.co/settings/tokens',
			'availabilityUrl' => 'https://huggingface.co/api/whoami-v2',
			'description'    => function_exists( '__' )
				? __( 'Inference Router proxying many model providers.', 'b-all-in-one-ai-providers' )
				: 'Inference Router proxying many model providers.',
			'logoFile'       => 'huggingface.svg',
			'directoryClass' => HuggingFaceModelMetadataDirectory::class,
			'modelClass'     => HuggingFaceTextGenerationModel::class,
		);
	}
}
