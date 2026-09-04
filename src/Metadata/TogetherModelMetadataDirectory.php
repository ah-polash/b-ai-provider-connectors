<?php
/**
 * Together Model Metadata Directory.
 *
 * @package BAllInOneAIProviders
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Metadata;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BPlugins\AllInOneAIProviders\Base\BaseModelMetadataDirectory;
use BPlugins\AllInOneAIProviders\Provider\TogetherProvider;

class TogetherModelMetadataDirectory extends BaseModelMetadataDirectory {
	protected static function providerClass(): string {
		return TogetherProvider::class;
	}
	protected function modelIdFilter( string $model_id ): bool {
		// Skip non-chat artefacts.
		$lower = strtolower( $model_id );
		foreach ( array( 'embedding', 'rerank', 'image', 'moderation' ) as $needle ) {
			if ( false !== strpos( $lower, $needle ) ) {
				return false;
			}
		}
		return true;
	}
}
