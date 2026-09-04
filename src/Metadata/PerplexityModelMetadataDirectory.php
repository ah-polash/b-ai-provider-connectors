<?php
/**
 * Perplexity Model Metadata Directory.
 *
 * @package BAllInOneAIProviders
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Metadata;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BPlugins\AllInOneAIProviders\Base\BaseModelMetadataDirectory;
use BPlugins\AllInOneAIProviders\Provider\PerplexityProvider;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\Http\Exception\ResponseException;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;

class PerplexityModelMetadataDirectory extends BaseModelMetadataDirectory {

	/**
	 * Publicly documented Sonar models. Update as Perplexity publishes new ones.
	 *
	 * @var list<array{id:string,name:string}>
	 */
	private const HARDCODED_MODELS = array(
		array( 'id' => 'sonar', 'name' => 'Sonar' ),
		array( 'id' => 'sonar-pro', 'name' => 'Sonar Pro' ),
		array( 'id' => 'sonar-reasoning', 'name' => 'Sonar Reasoning' ),
		array( 'id' => 'sonar-reasoning-pro', 'name' => 'Sonar Reasoning Pro' ),
		array( 'id' => 'sonar-deep-research', 'name' => 'Sonar Deep Research' ),
	);

	protected static function providerClass(): string {
		return PerplexityProvider::class;
	}

	/**
	 * Overrides the default `/models` GET — Perplexity doesn't have one. Instead,
	 * probe `/chat/completions` with a malformed body to verify auth, and
	 * synthesise the model list locally on success.
	 *
	 * @return array<string, ModelMetadata>
	 */
	protected function sendListModelsRequest(): array {
		$request = $this->createRequest(
			HttpMethodEnum::POST(),
			'chat/completions',
			array( 'Content-Type' => 'application/json' ),
			'{}'
		);
		$request  = $this->getRequestAuthentication()->authenticateRequest( $request );
		$response = $this->getHttpTransporter()->send( $request );

		$status = $response->getStatusCode();
		if ( 401 === $status || 403 === $status ) {
			throw ResponseException::fromInvalidData( 'Perplexity', 'auth', 'Invalid or unauthorised API key.' );
		}

		$capabilities = $this->defaultCapabilities();
		$options      = $this->defaultSupportedOptions();
		$map          = array();
		foreach ( self::HARDCODED_MODELS as $modelData ) {
			$map[ $modelData['id'] ] = new ModelMetadata( $modelData['id'], $modelData['name'], $capabilities, $options );
		}
		return $map;
	}
}
