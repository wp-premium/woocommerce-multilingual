<?php
/*
  Plugin Name: WooCommerce Multilingual
  Plugin URI: http://wpml.org/documentation/related-projects/woocommerce-multilingual/
  Description: Allows running fully multilingual e-Commerce sites with WooCommerce and WPML. <a href="http://wpml.org/documentation/related-projects/woocommerce-multilingual/">Documentation</a>.
  Author: OnTheGoSystems
  Author URI: http://www.onthegosystems.com/
  Text Domain: woocommerce-multilingual
  Version: 3.8.6
*/

if( defined( 'WCML_VERSION' ) ) return;

define( 'WCML_VERSION', '3.8.6' );
define( 'WCML_PLUGIN_PATH', dirname( __FILE__ ) );
define( 'WCML_PLUGIN_FOLDER', basename( WCML_PLUGIN_PATH ) );
define( 'WCML_LOCALE_PATH', WCML_PLUGIN_PATH . '/locale' );
define( 'WPML_LOAD_API_SUPPORT', true );
define( 'WCML_PLUGIN_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );

include WCML_PLUGIN_PATH . '/inc/constants.php';
require WCML_PLUGIN_PATH . '/inc/missing-php-functions.php';
include WCML_PLUGIN_PATH . '/inc/installer-loader.php';
include WCML_PLUGIN_PATH . '/inc/wcml-core-functions.php';

if ( version_compare( PHP_VERSION, '5.3.0' ) >= 0 ) {
    require WCML_PLUGIN_PATH . '/embedded/autoload.php';
} else {
    require WCML_PLUGIN_PATH . '/embedded/autoload_52.php';
}

// Load WooCommerce Multilingual when WPML is active
add_action( 'wpml_loaded', array( 'woocommerce_wpml', 'instance' ) );

// Load WooCommerce Multilingual when WPML is NOT active
add_action('plugins_loaded', 'wpml_wcml_startup', 10000);
function wpml_wcml_startup(){
    if( !did_action( 'wpml_loaded' ) ){
        global $woocommerce_wpml;
        $woocommerce_wpml = new woocommerce_wpml();
    }
}


