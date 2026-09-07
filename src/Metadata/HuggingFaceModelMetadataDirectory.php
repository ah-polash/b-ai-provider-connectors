<?php
/**
 * Hugging Face Model Metadata Directory.
 *
 * @package BAIProviderConnectors
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Metadata;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BPlugins\AllInOneAIProviders\Base\BaseModelMetadataDirectory;
use BPlugins\AllInOneAIProviders\Provider\HuggingFaceProvider;

class HuggingFaceModelMetadataDirectory extends BaseModelMetadataDirectory {
	protected static function providerClass(): string {
		return HuggingFaceProvider::class;
	}
	protected function modelIdFilter( string $model_id ): bool {
		$lower = strtolower( $model_id );
		foreach ( array( 'embedding', 'image', 'audio', 'speech' ) as $needle ) {
			if ( false !== strpos( $lower, $needle ) ) {
				return false;
			}
		}
		return true;
	}
}
