<?php
/*
 * Register the assets from this repository.
 *
 * DO NOT LOAD this file directly: follow the instructions in loader.php
 *
 * REMEMBER to increase the loader number in loader.php after every major update.
 */

if ( ! function_exists( 'otgs_icons_register_assets' ) ) {
	
	function otgs_icons_register_assets( $assets_data, $assets_version ) {
		/*
		 * Assets handles, in constants.
		 * Note that we define them at init.
		 */
		define( 'OTGS_ASSETS_ICONS_STYLES', 'otgs-icons' );
		
		wp_register_style( OTGS_ASSETS_ICONS_STYLES, $assets_data[ 'url' ] . '/css/otgs-icons.css', array(), $assets_version );
	}

}