<?php
/*
 * Load the shared OTGS icons library, on demand.
 *
 * =================
 * Usage
 * =================
 * $vendor_root_url = [ URL of the root of your relative vendor directory housing this repository ]
 * require_once( [ path to the root of your relative vendor directory housing this repository ] .  '/otgs/icons/loader.php' );
 *
 * =================
 * Restrictions
 * =================
 * - Assets are registered at init:10
 * - Their handles are stored in constants that you can use as dependencies, on assets registered after init:10.
 */

if ( ! isset( $vendor_root_url ) ) {
	return;
}

/*
 * OTGS icons version - increase after every major update.
 */
$otg_icons_version = 100;

/*
 * =================
 * ||   WARNING   ||
 * =================
 *
 * DO NOT EDIT below this line.
 */

global $otg_icons_versions;
if ( ! isset( $otg_icons_versions ) ) {
    $otg_icons_versions = array();
}
$otg_icons_versions[ $otg_icons_version ] = array(
	'url' => $vendor_root_url . '/otgs/icons',
	'path' => dirname( __FILE__ )
);

if ( ! has_action( 'init', 'otgs_icons_register' ) ) {
	add_action( 'init', 'otgs_icons_register' );
}

if ( ! function_exists( 'otgs_icons_register' ) ) {
	function otgs_icons_register() {
		global $otg_icons_versions;
		$latest = 0;
		
		foreach ( $otg_icons_versions as $version => $root_utl ) {
			if ( $version > $latest ) {
				$latest = $version;
			}
		}
		
		require_once( $otg_icons_versions[ $latest ]['path'] . '/register.php' );
        otgs_icons_register_assets( $otg_icons_versions[ $latest ], $latest );
	}
}