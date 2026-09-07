<?php
/**
 * Model metadata directory for custom provider slot 2.
 *
 * @package BPluginsAIProviderConnectors
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Custom;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BPlugins\AllInOneAIProviders\Base\BaseModelMetadataDirectory;

/**
 * Directory class for custom slot 2.
 *
 * @since 1.0.0
 */
class CustomModelMetadataDirectory2 extends BaseModelMetadataDirectory {

	/**
	 * {@inheritDoc}
	 */
	protected static function providerClass(): string {
		return CustomProvider2::class;
	}
}
