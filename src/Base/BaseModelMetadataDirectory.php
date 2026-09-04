<?php
/**
 * Base model-metadata directory for OpenAI-compatible providers.
 *
 * Provides a default text-generation capability/option matrix so subclasses
 * only need to point at their provider class (for URL construction) and,
 * optionally, override `modelIdFilter()` to drop non-text models from the list.
 *
 * @package BAllInOneAIProviders
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\Http\Exception\ResponseException;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleModelMetadataDirectory;

/**
 * Abstract base for OpenAI-compatible model metadata directories.
 *
 * @since 1.2.0
 */
abstract class BaseModelMetadataDirectory extends AbstractOpenAiCompatibleModelMetadataDirectory {

	/**
	 * Returns the fully qualified provider class (used for URL building).
	 *
	 * @return class-string<BaseProvider>
	 */
	abstract protected static function providerClass(): string;

	/**
	 * Optional filter applied to each model ID returned by the provider's
	 * `/models` endpoint. Default keeps every model.
	 *
	 * @param string $model_id Raw model identifier from the API.
	 * @return bool True to keep the model, false to skip it.
	 */
	protected function modelIdFilter( string $model_id ): bool {
		return true;
	}

	/**
	 * Default text-generation option set advertised for every model.
	 *
	 * @return list<SupportedOption>
	 */
	protected function defaultSupportedOptions(): array {
		return array(
			new SupportedOption( OptionEnum::systemInstruction() ),
			new SupportedOption( OptionEnum::maxTokens() ),
			new SupportedOption( OptionEnum::temperature() ),
			new SupportedOption( OptionEnum::topP() ),
			new SupportedOption( OptionEnum::stopSequences() ),
			new SupportedOption( OptionEnum::candidateCount() ),
			new SupportedOption( OptionEnum::outputMimeType(), array( 'text/plain', 'application/json' ) ),
			new SupportedOption( OptionEnum::customOptions() ),
			new SupportedOption(
				OptionEnum::inputModalities(),
				array(
					array( ModalityEnum::text() ),
				)
			),
			new SupportedOption( OptionEnum::outputModalities(), array( array( ModalityEnum::text() ) ) ),
		);
	}

	/**
	 * Default capability set advertised for every model.
	 *
	 * @return list<CapabilityEnum>
	 */
	protected function defaultCapabilities(): array {
		return array(
			CapabilityEnum::textGeneration(),
			CapabilityEnum::chatHistory(),
		);
	}

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

	/**
	 * {@inheritDoc}
	 */
	protected function parseResponseToModelMetadataList( Response $response ): array {
		$responseData = $response->getData();

		// Most OpenAI-compatible providers return { data: [ { id, ... } ] }.
		$models = array();
		if ( isset( $responseData['data'] ) && is_array( $responseData['data'] ) ) {
			$models = $responseData['data'];
		} elseif ( is_array( $responseData ) && ( array() === $responseData || array_keys( $responseData ) === range( 0, count( $responseData ) - 1 ) ) ) {
			$models = $responseData;
		}

		if ( empty( $models ) ) {
			throw ResponseException::fromMissingData( esc_html( static::providerClass()::providerMetadata()->getName() ), 'data' );
		}

		$capabilities      = $this->defaultCapabilities();
		$supportedOptions  = $this->defaultSupportedOptions();
		$out               = array();

		foreach ( $models as $modelData ) {
			if ( ! is_array( $modelData ) || empty( $modelData['id'] ) || ! is_string( $modelData['id'] ) ) {
				continue;
			}
			$modelId = $modelData['id'];
			if ( ! $this->modelIdFilter( $modelId ) ) {
				continue;
			}

			$displayName = isset( $modelData['display_name'] ) && is_string( $modelData['display_name'] )
				? $modelData['display_name']
				: ( isset( $modelData['name'] ) && is_string( $modelData['name'] ) ? $modelData['name'] : $modelId );

			$out[] = new ModelMetadata( $modelId, $displayName, $capabilities, $supportedOptions );
		}

		usort(
			$out,
			static function ( ModelMetadata $a, ModelMetadata $b ): int {
				return strcmp( $a->getId(), $b->getId() );
			}
		);

		return $out;
	}
}
