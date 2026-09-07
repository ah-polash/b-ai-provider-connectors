<?php
/**
 * Text generation model for custom provider slot 5.
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
 * Model class for custom slot 5.
 *
 * @since 1.0.0
 */
class CustomTextGenerationModel5 extends BaseTextGenerationModel {

	/**
	 * {@inheritDoc}
	 */
	protected static function providerClass(): string {
		return CustomProvider5::class;
	}
}
