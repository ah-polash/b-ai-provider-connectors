<?php
/**
 * Deep Seek Text Generation Model.
 *
 * @package BAllInOneAIProviders
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Models;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BPlugins\AllInOneAIProviders\Base\BaseTextGenerationModel;
use BPlugins\AllInOneAIProviders\Provider\DeepSeekProvider;

class DeepSeekTextGenerationModel extends BaseTextGenerationModel {
	protected static function providerClass(): string {
		return DeepSeekProvider::class;
	}
}
