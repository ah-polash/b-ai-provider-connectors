<?php
/**
 * Base text-generation model for OpenAI-compatible providers.
 *
 * Inherits the entire chat/completions request and response handling from
 * the AI Client's OpenAI-compatible base; subclasses only need to point at
 * their provider class (for URL construction).
 *
 * @package BAllInOneAIProviders
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleTextGenerationModel;

/**
 * Abstract base for OpenAI-compatible text generation models.
 *
 * @since 1.2.0
 */
abstract class BaseTextGenerationModel extends AbstractOpenAiCompatibleTextGenerationModel {

	/**
	 * Returns the fully qualified provider class (used for URL building).
	 *
	 * @return class-string<BaseProvider>
	 */
	abstract protected static function providerClass(): string;

	/**
	 * {@inheritDoc}
	 */
	protected function createRequest( HttpMethodEnum $method, string $path, array $headers = array(), $data = null ): Request {
		$providerClass = static::providerClass();
		return new Request(
			$method,
			$providerClass::url( $path ),
			$headers,
			$data
		);
	}
}
