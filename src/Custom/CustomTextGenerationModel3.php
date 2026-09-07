<?php
/**
 * Text generation model for custom provider slot 3.
 *
 * @package BPluginsAIProviderConnectors
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Custom;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BPlugins\AllInOneAIProviders\Base\BaseTextGenerationModel;

/**
 * Model class for custom slot 3.
 *
 * @since 1.0.0
 */
class CustomTextGenerationModel3 extends BaseTextGenerationModel {

	/**
	 * {@inheritDoc}
	 */
	protected static function providerClass(): string {
		return CustomProvider3::class;
	}
}
