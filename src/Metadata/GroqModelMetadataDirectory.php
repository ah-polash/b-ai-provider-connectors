<?php
/**
 * Groq Model Metadata Directory.
 *
 * @package BAIProviderConnectors
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Metadata;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BPlugins\AllInOneAIProviders\Base\BaseModelMetadataDirectory;
use BPlugins\AllInOneAIProviders\Provider\GroqProvider;

class GroqModelMetadataDirectory extends BaseModelMetadataDirectory {
	protected static function providerClass(): string {
		return GroqProvider::class;
	}
	protected function modelIdFilter( string $model_id ): bool {
		// Skip Whisper (audio) and TTS-only models from the chat completion list.
		$lower = strtolower( $model_id );
		return false === strpos( $lower, 'whisper' ) && false === strpos( $lower, 'tts' );
	}
}
