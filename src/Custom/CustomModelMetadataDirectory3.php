<?php
/**
 * Model metadata directory for custom provider slot 3.
 *
 * @package BAIProviderConnectors
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Custom;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BPlugins\AllInOneAIProviders\Base\BaseModelMetadataDirectory;

/**
 * Directory class for custom slot 3.
 *
 * @since 1.0.0
 */
class CustomModelMetadataDirectory3 extends BaseModelMetadataDirectory {

	/**
	 * {@inheritDoc}
	 */
	protected static function providerClass(): string {
		return CustomProvider3::class;
	}
}
