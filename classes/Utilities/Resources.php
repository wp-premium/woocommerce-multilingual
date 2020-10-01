<?php

namespace WCML\Utilities;

use function WPML\FP\partial;

class Resources {
	// enqueueApp :: string $app -> ( string $localizeData )
	public static function enqueueApp( $app ) {
		return partial( [ '\WPML\LIB\WP\App\Resources', 'enqueue' ],
			$app, WCML_PLUGIN_URL, WCML_PLUGIN_PATH, WCML_VERSION, 'woocommerce-multilingual'
		);
	}
}
