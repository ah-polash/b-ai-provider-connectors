<?php
/**
 * Fireworks Model Metadata Directory.
 *
 * @package BPluginsAIProviderConnectors
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Metadata;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BPlugins\AllInOneAIProviders\Base\BaseModelMetadataDirectory;
use BPlugins\AllInOneAIProviders\Provider\FireworksProvider;

class FireworksModelMetadataDirectory extends BaseModelMetadataDirectory {
	protected static function providerClass(): string {
		return FireworksProvider::class;
	}
	protected function modelIdFilter( string $model_id ): bool {
		$lower = strtolower( $model_id );
		foreach ( array( 'embedding', 'image', 'video' ) as $needle ) {
			if ( false !== strpos( $lower, $needle ) ) {
				return false;
			}
		}
		return true;
	}
}
