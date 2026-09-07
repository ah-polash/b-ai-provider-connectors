<?php
/**
 * Base provider for OpenAI-compatible (and near-compatible) AI services.
 *
 * Concrete subclasses define a single `config()` method that returns a
 * descriptor — id, name, base URL, credentials URL, description, logo path,
 * and the model directory / text-generation model class names. Everything else
 * (URL building, provider metadata, availability probe) is shared.
 *
 * @package BPluginsAIProviderConnectors
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiBasedModelMetadataDirectory;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiProvider;
use WordPress\AiClient\Providers\ApiBasedImplementation\ListModelsApiBasedProviderAvailability;
use WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Http\Enums\RequestAuthenticationMethod;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\AiClient;

/**
 * Abstract base for bPlugins AI Provider Connectors provider classes.
 *
 * @since 1.2.0
 */
abstract class BaseProvider extends AbstractApiProvider {

	/**
	 * Returns the static configuration descriptor for this provider.
	 *
	 * Expected keys: id, name, baseUrl, credentialsUrl, description,
	 * logoFile, directoryClass, modelClass. Optional: availabilityUrl (absolute URL
	 * of an endpoint that requires authentication, for providers whose model list is public).
	 *
	 * @return array<string, mixed>
	 */
	abstract protected static function config(): array;

	/**
	 * Returns the absolute logo path, or null if the SVG isn't present.
	 *
	 * @return string|null
	 */
	protected static function logoPath(): ?string {
		$cfg = static::config();
		if ( empty( $cfg['logoFile'] ) ) {
			return null;
		}
		$path = BAIOAP_DIR . 'assets/' . $cfg['logoFile'];
		return file_exists( $path ) ? $path : null;
	}

	/**
	 * {@inheritDoc}
	 */
	protected static function baseUrl(): string {
		return (string) static::config()['baseUrl'];
	}

	/**
	 * {@inheritDoc}
	 */
	protected static function createModel(
		ModelMetadata $modelMetadata,
		ProviderMetadata $providerMetadata
	): ModelInterface {
		$modelClass = static::config()['modelClass'];
		return new $modelClass( $modelMetadata, $providerMetadata );
	}

	/**
	 * {@inheritDoc}
	 */
	protected static function createProviderMetadata(): ProviderMetadata {
		$cfg = static::config();

		$args = array(
			$cfg['id'],
			$cfg['name'],
			ProviderTypeEnum::cloud(),
			! empty( $cfg['credentialsUrl'] ) ? (string) $cfg['credentialsUrl'] : null,
			RequestAuthenticationMethod::apiKey(),
		);

		// Provider description support was added in AiClient 1.2.0.
		if ( version_compare( AiClient::VERSION, '1.2.0', '>=' ) ) {
			$args[] = (string) ( $cfg['description'] ?? '' );
		}
		// Provider logoPath support was added in AiClient 1.3.0.
		if ( version_compare( AiClient::VERSION, '1.3.0', '>=' ) ) {
			$args[] = static::logoPath();
		}

		return new ProviderMetadata( ...$args );
	}

	/**
	 * {@inheritDoc}
	 */
	protected static function createProviderAvailability(): ProviderAvailabilityInterface {
		$cfg       = static::config();
		$directory = static::modelMetadataDirectory();

		// Providers whose model list is public need an authenticated probe instead.
		if ( ! empty( $cfg['availabilityUrl'] ) && $directory instanceof AbstractApiBasedModelMetadataDirectory ) {
			return new AuthenticatedEndpointAvailability( $directory, (string) $cfg['availabilityUrl'] );
		}

		return new ListModelsApiBasedProviderAvailability( $directory );
	}

	/**
	 * {@inheritDoc}
	 */
	protected static function createModelMetadataDirectory(): ModelMetadataDirectoryInterface {
		$dirClass = static::config()['directoryClass'];
		return new $dirClass();
	}
}
