<?php
/**
 * Mistral Model Metadata Directory.
 *
 * @package BAllInOneAIProviders
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Metadata;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BPlugins\AllInOneAIProviders\Base\BaseModelMetadataDirectory;
use BPlugins\AllInOneAIProviders\Provider\MistralProvider;

class MistralModelMetadataDirectory extends BaseModelMetadataDirectory {
	protected static function providerClass(): string {
		return MistralProvider::class;
	}
	protected function modelIdFilter( string $model_id ): bool {
		// Skip embedding-only models.
		return false === stripos( $model_id, 'embed' );
	}
}
