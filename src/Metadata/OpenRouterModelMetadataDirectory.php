<?php
/**
 * Open Router Model Metadata Directory.
 *
 * @package BAIProviderConnectors
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Metadata;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BPlugins\AllInOneAIProviders\Base\BaseModelMetadataDirectory;
use BPlugins\AllInOneAIProviders\Provider\OpenRouterProvider;

/**
 * Model metadata directory for OpenRouter.
 *
 * @since 1.2.0
 */
class OpenRouterModelMetadataDirectory extends BaseModelMetadataDirectory {
	protected static function providerClass(): string {
		return OpenRouterProvider::class;
	}
}
