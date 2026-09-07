<?php
/**
 * Deep Seek Model Metadata Directory.
 *
 * @package BPluginsAIProviderConnectors
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Metadata;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BPlugins\AllInOneAIProviders\Base\BaseModelMetadataDirectory;
use BPlugins\AllInOneAIProviders\Provider\DeepSeekProvider;

class DeepSeekModelMetadataDirectory extends BaseModelMetadataDirectory {
	protected static function providerClass(): string {
		return DeepSeekProvider::class;
	}
}
