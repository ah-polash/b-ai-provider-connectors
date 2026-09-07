<?php
/**
 * Perplexity Text Generation Model.
 *
 * @package BPluginsAIProviderConnectors
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Models;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BPlugins\AllInOneAIProviders\Base\BaseTextGenerationModel;
use BPlugins\AllInOneAIProviders\Provider\PerplexityProvider;

class PerplexityTextGenerationModel extends BaseTextGenerationModel {
	protected static function providerClass(): string {
		return PerplexityProvider::class;
	}
}
