<?php
/**
 * Custom provider slot 5 (an OpenAI-compatible endpoint configured by the site owner).
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
 * Provider class for custom slot 5.
 *
 * @since 1.0.0
 */
class CustomProvider5 extends BaseProvider {

	const SLOT = 5;

	/**
	 * {@inheritDoc}
	 */
	protected static function config(): array {
		return \BAIOAP_Catalog::custom_config( self::SLOT, CustomModelMetadataDirectory5::class, CustomTextGenerationModel5::class );
	}
}
