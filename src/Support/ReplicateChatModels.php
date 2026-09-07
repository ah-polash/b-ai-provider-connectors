<?php
/**
 * Replicate Chat Models.
 *
 * @package BPluginsAIProviderConnectors
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Curated list of Replicate chat models known to use the Llama-style input
 * schema. Each must accept at least `prompt` (string) and optionally
 * `system_prompt`, `max_tokens`, `temperature`, `top_p`.
 */
final class ReplicateChatModels {

	/** @return list<array{id:string,name:string}> */
	public static function all(): array {
		return array(
			array( 'id' => 'meta/meta-llama-3-70b-instruct', 'name' => 'Llama 3 70B Instruct' ),
			array( 'id' => 'meta/meta-llama-3-8b-instruct',  'name' => 'Llama 3 8B Instruct' ),
			array( 'id' => 'meta/meta-llama-3.1-405b-instruct', 'name' => 'Llama 3.1 405B Instruct' ),
			array( 'id' => 'mistralai/mixtral-8x7b-instruct-v0.1', 'name' => 'Mixtral 8x7B Instruct' ),
		);
	}
}
