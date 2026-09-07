<?php
/**
 * Fireworks Text Generation Model.
 *
 * @package BPluginsAIProviderConnectors
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Models;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BPlugins\AllInOneAIProviders\Base\BaseTextGenerationModel;
use BPlugins\AllInOneAIProviders\Provider\FireworksProvider;

class FireworksTextGenerationModel extends BaseTextGenerationModel {
	protected static function providerClass(): string {
		return FireworksProvider::class;
	}
}
