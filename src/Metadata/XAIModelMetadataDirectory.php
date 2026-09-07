<?php
/**
 * X A I Model Metadata Directory.
 *
 * @package BAIProviderConnectors
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Metadata;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BPlugins\AllInOneAIProviders\Base\BaseModelMetadataDirectory;
use BPlugins\AllInOneAIProviders\Provider\XAIProvider;

class XAIModelMetadataDirectory extends BaseModelMetadataDirectory {
	protected static function providerClass(): string {
		return XAIProvider::class;
	}
	protected function modelIdFilter( string $model_id ): bool {
		// Keep chat-capable models only.
		return false === stripos( $model_id, 'image' ) && false === stripos( $model_id, 'embedding' );
	}
}
