<?php
/**
 * X A I Provider.
 *
 * @package BAllInOneAIProviders
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Provider;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BPlugins\AllInOneAIProviders\Base\BaseProvider;
use BPlugins\AllInOneAIProviders\Metadata\XAIModelMetadataDirectory;
use BPlugins\AllInOneAIProviders\Models\XAITextGenerationModel;

class XAIProvider extends BaseProvider {
	protected static function config(): array {
		return array(
			'id'             => 'xai',
			'name'           => 'xAI',
			'baseUrl'        => 'https://api.x.ai/v1', // phpcs:ignore PluginCheck.CodeAnalysis.AIProvider.DirectIntegration -- This plugin registers the provider with the WordPress AI Client; the base URL is required for that registration.
			'credentialsUrl' => 'https://console.x.ai/',
			'description'    => function_exists( '__' )
				? __( 'Grok models from xAI.', 'b-all-in-one-ai-providers' )
				: 'Grok models from xAI.',
			'logoFile'       => 'xai.svg',
			'directoryClass' => XAIModelMetadataDirectory::class,
			'modelClass'     => XAITextGenerationModel::class,
		);
	}
}
