<?php
/**
 * Custom provider slot 3 (an OpenAI-compatible endpoint configured by the site owner).
 *
 * @package BPluginsAIProviderConnectors
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Custom;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BPlugins\AllInOneAIProviders\Base\BaseProvider;

/**
 * Provider class for custom slot 3.
 *
 * @since 1.0.0
 */
class CustomProvider3 extends BaseProvider {

	const SLOT = 3;

	/**
	 * {@inheritDoc}
	 */
	protected static function config(): array {
		return \BAIOAP_Catalog::custom_config( self::SLOT, CustomModelMetadataDirectory3::class, CustomTextGenerationModel3::class );
	}
}
