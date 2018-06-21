<?php
/**
 * Include this file to use OTGS-Icons
 */

if( !defined( 'OTGS_ICONS_ROOT_URL' ) )
	return;

if( !defined( 'OTGS_ICONS_VERSION' ) )
	define( 'OTGS_ICONS_VERSION', '1.0' );

if( ! has_action( 'wp_enqueue_scripts', 'otgs_icons' ) )
	add_action( 'wp_enqueue_scripts', 'otgs_icons' );

if( ! function_exists( 'otgs_icons' ) ) {
	function otgs_icons() {
		wp_register_style( 'otgs-icons', OTGS_ICONS_ROOT_URL . '/css/otgs-icons.css', array(), OTGS_ICONS_VERSION );
		wp_enqueue_style( 'otgs-icons' );
	}
}
