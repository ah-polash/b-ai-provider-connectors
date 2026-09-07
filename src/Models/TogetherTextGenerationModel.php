<?php
/**
 * Together Text Generation Model.
 *
 * @package BAIProviderConnectors
 */

declare(strict_types=1);

namespace BPlugins\AllInOneAIProviders\Models;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BPlugins\AllInOneAIProviders\Base\BaseTextGenerationModel;
use BPlugins\AllInOneAIProviders\Provider\TogetherProvider;

class TogetherTextGenerationModel extends BaseTextGenerationModel {
	protected static function providerClass(): string {
		return TogetherProvider::class;
	}
}
