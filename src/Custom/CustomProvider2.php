<?php
/**
 * Custom provider slot 2 (an OpenAI-compatible endpoint configured by the site owner).
 *
 * @package BAllInOneAIProviders
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Custom;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BPlugins\AllInOneAIProviders\Base\BaseProvider;

/**
 * Provider class for custom slot 2.
 *
 * @since 1.0.0
 */
class CustomProvider2 extends BaseProvider {

	const SLOT = 2;

	/**
	 * {@inheritDoc}
	 */
	protected static function config(): array {
		return \BAIOAP_Catalog::custom_config( self::SLOT, CustomModelMetadataDirectory2::class, CustomTextGenerationModel2::class );
	}
}
