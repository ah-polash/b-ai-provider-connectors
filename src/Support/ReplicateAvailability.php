<?php
/**
 * Replicate Availability.
 *
 * @package BAllInOneAIProviders
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Exception;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiBasedModelMetadataDirectory;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;

/**
 * Availability probe for Replicate: hits /v1/account to verify the token.
 */
class ReplicateAvailability implements ProviderAvailabilityInterface {

	/** @var AbstractApiBasedModelMetadataDirectory */
	private $directory;

	public function __construct( AbstractApiBasedModelMetadataDirectory $directory ) {
		$this->directory = $directory;
	}

	public function isConfigured(): bool {
		try {
			$this->directory->listModelMetadata();
			return true;
		} catch ( Exception $e ) {
			return false;
		}
	}
}
