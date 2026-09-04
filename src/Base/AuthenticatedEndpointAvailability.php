<?php
/**
 * Availability check that calls an endpoint which requires authentication.
 *
 * Some providers (OpenRouter, Hugging Face) expose their model list publicly, so the
 * AI Client's default "list models" probe would report *any* API key as valid. This
 * probe calls an authenticated endpoint instead, so an invalid key is reported as such.
 *
 * @package BAllInOneAIProviders
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Exception;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiBasedModelMetadataDirectory;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;

/**
 * Verifies credentials with a GET request to an authenticated endpoint.
 *
 * @since 1.0.0
 */
class AuthenticatedEndpointAvailability implements ProviderAvailabilityInterface {

	/**
	 * Model metadata directory, used for its HTTP transporter and authentication.
	 *
	 * @var AbstractApiBasedModelMetadataDirectory
	 */
	private $directory;

	/**
	 * Absolute URL of an endpoint that returns 2xx only with valid credentials.
	 *
	 * @var string
	 */
	private $url;

	/**
	 * Constructor.
	 *
	 * @param AbstractApiBasedModelMetadataDirectory $directory Directory carrying transporter + auth.
	 * @param string                                 $url       Authenticated endpoint URL.
	 */
	public function __construct( AbstractApiBasedModelMetadataDirectory $directory, string $url ) {
		$this->directory = $directory;
		$this->url       = $url;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isConfigured(): bool {
		try {
			$request  = new Request( HttpMethodEnum::GET(), $this->url, array( 'Accept' => 'application/json' ) );
			$request  = $this->directory->getRequestAuthentication()->authenticateRequest( $request );
			$response = $this->directory->getHttpTransporter()->send( $request );
			return $response->isSuccessful();
		} catch ( Exception $e ) {
			return false;
		}
	}
}
