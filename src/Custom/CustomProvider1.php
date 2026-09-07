<?php
/**
 * Custom provider slot 1 (an OpenAI-compatible endpoint configured by the site owner).
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
 * Provider class for custom slot 1.
 *
 * @since 1.0.0
 */
class CustomProvider1 extends BaseProvider {

	const SLOT = 1;

	/**
	 * {@inheritDoc}
	 */
	protected static function config(): array {
		return \BAIOAP_Catalog::custom_config( self::SLOT, CustomModelMetadataDirectory1::class, CustomTextGenerationModel1::class );
	}
}
