<?php
/**
 * Text generation model for custom provider slot 1.
 *
 * @package BAIProviderConnectors
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Custom;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BPlugins\AllInOneAIProviders\Base\BaseTextGenerationModel;

/**
 * Model class for custom slot 1.
 *
 * @since 1.0.0
 */
class CustomTextGenerationModel1 extends BaseTextGenerationModel {

	/**
	 * {@inheritDoc}
	 */
	protected static function providerClass(): string {
		return CustomProvider1::class;
	}
}
