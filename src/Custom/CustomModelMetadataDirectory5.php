<?php
/**
 * Model metadata directory for custom provider slot 5.
 *
 * @package BAllInOneAIProviders
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Custom;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BPlugins\AllInOneAIProviders\Base\BaseModelMetadataDirectory;

/**
 * Directory class for custom slot 5.
 *
 * @since 1.0.0
 */
class CustomModelMetadataDirectory5 extends BaseModelMetadataDirectory {

	/**
	 * {@inheritDoc}
	 */
	protected static function providerClass(): string {
		return CustomProvider5::class;
	}
}
