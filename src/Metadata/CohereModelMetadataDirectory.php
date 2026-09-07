<?php
/**
 * Cohere Model Metadata Directory.
 *
 * @package BAIProviderConnectors
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Metadata;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BPlugins\AllInOneAIProviders\Base\BaseModelMetadataDirectory;
use BPlugins\AllInOneAIProviders\Provider\CohereProvider;

class CohereModelMetadataDirectory extends BaseModelMetadataDirectory {
	protected static function providerClass(): string {
		return CohereProvider::class;
	}
	protected function modelIdFilter( string $model_id ): bool {
		$lower = strtolower( $model_id );
		foreach ( array( 'embed', 'rerank', 'classify' ) as $needle ) {
			if ( false !== strpos( $lower, $needle ) ) {
				return false;
			}
		}
		return true;
	}
}
