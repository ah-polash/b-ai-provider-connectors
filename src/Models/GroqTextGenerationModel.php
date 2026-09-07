<?php
/**
 * Groq Text Generation Model.
 *
 * @package BAIProviderConnectors
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Models;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BPlugins\AllInOneAIProviders\Base\BaseTextGenerationModel;
use BPlugins\AllInOneAIProviders\Provider\GroqProvider;

class GroqTextGenerationModel extends BaseTextGenerationModel {
	protected static function providerClass(): string {
		return GroqProvider::class;
	}
}
