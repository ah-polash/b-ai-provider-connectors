<?php
/**
 * Text generation model for custom provider slot 2.
 *
 * @package BAllInOneAIProviders
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Custom;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BPlugins\AllInOneAIProviders\Base\BaseTextGenerationModel;

/**
 * Model class for custom slot 2.
 *
 * @since 1.0.0
 */
class CustomTextGenerationModel2 extends BaseTextGenerationModel {

	/**
	 * {@inheritDoc}
	 */
	protected static function providerClass(): string {
		return CustomProvider2::class;
	}
}
