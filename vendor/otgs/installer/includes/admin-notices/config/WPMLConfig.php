<?php

namespace OTGS\Installer\AdminNotices;

class WPMLConfig {

	public static function pages() {
		if ( ! defined( 'WPML_PLUGIN_FOLDER' ) ) {
			return [];
		}

		return [
			WPML_PLUGIN_FOLDER . '/menu/languages.php',
			WPML_PLUGIN_FOLDER . '/menu/theme-localization.php',
			WPML_PLUGIN_FOLDER . '/menu/settings.php',
			WPML_PLUGIN_FOLDER . '/menu/support.php',
		];
	}
}
