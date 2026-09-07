<?php
/**
 * Replicate Model Metadata Directory.
 *
 * @package BPluginsAIProviderConnectors
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Metadata;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BPlugins\AllInOneAIProviders\Provider\ReplicateProvider;
use BPlugins\AllInOneAIProviders\Support\ReplicateChatModels;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiBasedModelMetadataDirectory;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\Http\Util\ResponseUtil;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;

class ReplicateModelMetadataDirectory extends AbstractApiBasedModelMetadataDirectory {

	protected function createRequest( HttpMethodEnum $method, string $path, array $headers = array(), $data = null ): Request {
		return new Request( $method, ReplicateProvider::url( $path ), $headers, $data );
	}

	/**
	 * Probes /v1/account to verify auth, then returns the hardcoded chat
	 * model list. Returning hardcoded metadata avoids the very expensive
	 * paginated /v1/models walk which can return thousands of non-chat models.
	 *
	 * @return array<string, ModelMetadata>
	 */
	protected function sendListModelsRequest(): array {
		$request  = $this->createRequest( HttpMethodEnum::GET(), 'account' );
		$request  = $this->getRequestAuthentication()->authenticateRequest( $request );
		$response = $this->getHttpTransporter()->send( $request );
		ResponseUtil::throwIfNotSuccessful( $response );

		$capabilities = array(
			CapabilityEnum::textGeneration(),
			CapabilityEnum::chatHistory(),
		);
		$options = array(
			new SupportedOption( OptionEnum::systemInstruction() ),
			new SupportedOption( OptionEnum::maxTokens() ),
			new SupportedOption( OptionEnum::temperature() ),
			new SupportedOption( OptionEnum::topP() ),
			new SupportedOption( OptionEnum::customOptions() ),
			new SupportedOption( OptionEnum::inputModalities(), array( array( ModalityEnum::text() ) ) ),
			new SupportedOption( OptionEnum::outputModalities(), array( array( ModalityEnum::text() ) ) ),
		);

		$map = array();
		foreach ( ReplicateChatModels::all() as $model ) {
			$map[ $model['id'] ] = new ModelMetadata( $model['id'], $model['name'], $capabilities, $options );
		}
		return $map;
	}
}
