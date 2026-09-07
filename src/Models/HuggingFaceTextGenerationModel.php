<?php
/**
 * Hugging Face Text Generation Model.
 *
 * @package BPluginsAIProviderConnectors
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Models;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BPlugins\AllInOneAIProviders\Base\BaseTextGenerationModel;
use BPlugins\AllInOneAIProviders\Provider\HuggingFaceProvider;

class HuggingFaceTextGenerationModel extends BaseTextGenerationModel {
	protected static function providerClass(): string {
		return HuggingFaceProvider::class;
	}
}
