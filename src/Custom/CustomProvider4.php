<?php
/**
 * Custom provider slot 4 (an OpenAI-compatible endpoint configured by the site owner).
 *
 * @package BAIProviderConnectors
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Custom;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BPlugins\AllInOneAIProviders\Base\BaseProvider;

/**
 * Provider class for custom slot 4.
 *
 * @since 1.0.0
 */
class CustomProvider4 extends BaseProvider {

	const SLOT = 4;

	/**
	 * {@inheritDoc}
	 */
	protected static function config(): array {
		return \BAIOAP_Catalog::custom_config( self::SLOT, CustomModelMetadataDirectory4::class, CustomTextGenerationModel4::class );
	}
}
