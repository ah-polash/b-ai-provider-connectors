<?php
/**
 * Open Router Text Generation Model.
 *
 * @package BAIProviderConnectors
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Models;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BPlugins\AllInOneAIProviders\Base\BaseTextGenerationModel;
use BPlugins\AllInOneAIProviders\Provider\OpenRouterProvider;

/**
 * Text generation model for OpenRouter.
 *
 * @since 1.2.0
 */
class OpenRouterTextGenerationModel extends BaseTextGenerationModel {
	protected static function providerClass(): string {
		return OpenRouterProvider::class;
	}
}
