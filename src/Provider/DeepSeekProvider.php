<?php
/**
 * Deep Seek Provider.
 *
 * @package BAllInOneAIProviders
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
			'baseUrl'        => 'https://api.deepseek.com',
			'credentialsUrl' => 'https://platform.deepseek.com/api_keys',
			'description'    => function_exists( '__' )
				? __( 'Reasoning and code models from DeepSeek.', 'b-all-in-one-ai-providers' )
				: 'Reasoning and code models from DeepSeek.',
			'logoFile'       => 'deepseek.svg',
			'directoryClass' => DeepSeekModelMetadataDirectory::class,
			'modelClass'     => DeepSeekTextGenerationModel::class,
		);
	}
}
