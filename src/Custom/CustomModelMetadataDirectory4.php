<?php
/**
 * Model metadata directory for custom provider slot 4.
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
 * Directory class for custom slot 4.
 *
 * @since 1.0.0
 */
class CustomModelMetadataDirectory4 extends BaseModelMetadataDirectory {

	/**
	 * {@inheritDoc}
	 */
	protected static function providerClass(): string {
		return CustomProvider4::class;
	}
}
