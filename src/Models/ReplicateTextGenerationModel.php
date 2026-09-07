<?php
/**
 * Replicate Text Generation Model.
 *
 * @package BPluginsAIProviderConnectors
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Models;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BPlugins\AllInOneAIProviders\Provider\ReplicateProvider;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiBasedModel;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\Http\Exception\ResponseException;
use WordPress\AiClient\Providers\Http\Util\ResponseUtil;
use WordPress\AiClient\Providers\Models\TextGeneration\Contracts\TextGenerationModelInterface;
use WordPress\AiClient\Results\DTO\Candidate;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Results\DTO\TokenUsage;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;

class ReplicateTextGenerationModel extends AbstractApiBasedModel implements TextGenerationModelInterface {

	final public function generateTextResult( array $prompt ): GenerativeAiResult {
		$config = $this->getConfig();

		// Translate the message list into Replicate's Llama-style input schema.
		$promptText      = $this->flattenMessagesToPrompt( $prompt );
		$systemPrompt    = $config->getSystemInstruction();
		$maxTokens       = $config->getMaxTokens();
		$temperature     = $config->getTemperature();
		$topP            = $config->getTopP();
		$customOptions   = $config->getCustomOptions();

		$input = array_filter(
			array_merge(
				array(
					'prompt'         => $promptText,
					'system_prompt'  => $systemPrompt,
					'max_tokens'     => $maxTokens,
					'temperature'    => $temperature,
					'top_p'          => $topP,
				),
				is_array( $customOptions ) ? $customOptions : array()
			),
			static function ( $value ) {
				return null !== $value;
			}
		);

		$body = array( 'input' => $input );

		$modelPath = 'models/' . $this->metadata()->getId() . '/predictions';
		$request   = new Request(
			HttpMethodEnum::POST(),
			ReplicateProvider::url( $modelPath ),
			array(
				'Content-Type' => 'application/json',
				// Synchronous prediction: server waits up to 60s for the result.
				'Prefer'       => 'wait=60',
			),
			$body,
			$this->getRequestOptions()
		);
		$request = $this->getRequestAuthentication()->authenticateRequest( $request );

		$response = $this->getHttpTransporter()->send( $request );
		ResponseUtil::throwIfNotSuccessful( $response );

		return $this->parseResponseToGenerativeAiResult( $response );
	}

	/**
	 * @param list<Message> $messages
	 */
	private function flattenMessagesToPrompt( array $messages ): string {
		$lines = array();
		foreach ( $messages as $message ) {
			$role  = $message->getRole() === MessageRoleEnum::model() ? 'assistant' : 'user';
			$texts = array();
			foreach ( $message->getParts() as $part ) {
				if ( $part instanceof MessagePart && $part->getType()->isText() ) {
					$texts[] = (string) $part->getText();
				}
			}
			if ( $texts ) {
				$lines[] = $role . ': ' . trim( implode( "\n", $texts ) );
			}
		}
		return implode( "\n", $lines );
	}

	private function parseResponseToGenerativeAiResult( Response $response ): GenerativeAiResult {
		$data = $response->getData();
		if ( ! is_array( $data ) ) {
			throw ResponseException::fromMissingData( 'Replicate', 'body' );
		}

		$status = isset( $data['status'] ) ? (string) $data['status'] : '';
		if ( 'succeeded' !== $status ) {
			throw new RuntimeException(
				esc_html(
					sprintf(
						'Replicate prediction did not complete synchronously (status=%s). Re-run the request; sync mode waits up to 60 seconds.',
						$status
					)
				)
			);
		}

		// Replicate's text-gen models return output as either an array of
		// string chunks or a single string. Concatenate either form.
		$output = isset( $data['output'] ) ? $data['output'] : '';
		if ( is_array( $output ) ) {
			$output = implode( '', array_map( 'strval', $output ) );
		} else {
			$output = (string) $output;
		}

		$candidate = new Candidate(
			new Message( MessageRoleEnum::model(), array( new MessagePart( $output ) ) ),
			FinishReasonEnum::stop()
		);

		// Replicate reports tokens under data.metrics for some models; tolerate absence.
		$inputTokens  = isset( $data['metrics']['input_token_count'] ) ? (int) $data['metrics']['input_token_count'] : 0;
		$outputTokens = isset( $data['metrics']['output_token_count'] ) ? (int) $data['metrics']['output_token_count'] : 0;
		$tokenUsage   = new TokenUsage( $inputTokens, $outputTokens, $inputTokens + $outputTokens );

		$id = isset( $data['id'] ) && is_string( $data['id'] ) ? $data['id'] : '';

		return new GenerativeAiResult(
			$id,
			array( $candidate ),
			$tokenUsage,
			$this->providerMetadata(),
			$this->metadata(),
			$data
		);
	}
}
