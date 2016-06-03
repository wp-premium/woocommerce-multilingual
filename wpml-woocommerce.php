<?php
/*
  Plugin Name: WooCommerce Multilingual
  Plugin URI: http://wpml.org/documentation/related-projects/woocommerce-multilingual/
  Description: Allows running fully multilingual e-Commerce sites with WooCommerce and WPML. <a href="http://wpml.org/documentation/related-projects/woocommerce-multilingual/">Documentation</a>.
  Author: OnTheGoSystems
  Author URI: http://www.onthegosystems.com/
  Text Domain: woocommerce-multilingual
  Version: 3.7.16
*/


if(defined('WCML_VERSION')) return;
define('WCML_VERSION', '3.7.16');
define('WCML_PLUGIN_PATH', dirname(__FILE__));
define('WCML_PLUGIN_FOLDER', basename(WCML_PLUGIN_PATH));
define('WCML_LOCALE_PATH', WCML_PLUGIN_PATH.'/locale');
define('WPML_LOAD_API_SUPPORT', true);

define('WCML_MULTI_CURRENCIES_DISABLED', 0);
define('WCML_MULTI_CURRENCIES_PER_LANGUAGE', 1); //obsolete - migrate to 2
define('WCML_MULTI_CURRENCIES_INDEPENDENT', 2);


require WCML_PLUGIN_PATH . '/inc/missing-php-functions.php';
require WCML_PLUGIN_PATH . '/inc/dependencies.class.php';
require WCML_PLUGIN_PATH . '/inc/store-pages.class.php';
require WCML_PLUGIN_PATH . '/inc/products.class.php';
require WCML_PLUGIN_PATH . '/inc/emails.class.php';
require WCML_PLUGIN_PATH . '/inc/upgrade.class.php';
require WCML_PLUGIN_PATH . '/inc/ajax-setup.class.php';
require WCML_PLUGIN_PATH . '/inc/wc-strings.class.php';
require WCML_PLUGIN_PATH . '/inc/terms.class.php';
require WCML_PLUGIN_PATH . '/inc/orders.class.php';
require WCML_PLUGIN_PATH . '/inc/requests.class.php';
require WCML_PLUGIN_PATH . '/inc/functions-troubleshooting.class.php';
require WCML_PLUGIN_PATH . '/inc/compatibility.class.php';
require WCML_PLUGIN_PATH . '/inc/endpoints.class.php';
require WCML_PLUGIN_PATH . '/inc/currency-switcher.class.php';
require WCML_PLUGIN_PATH . '/inc/xdomain-data.class.php';
require WCML_PLUGIN_PATH . '/inc/url-translation.class.php';
require WCML_PLUGIN_PATH . '/inc/class-wcml-tp-support.php';
require WCML_PLUGIN_PATH . '/inc/class-wcml-languages-upgrader.php';

require WCML_PLUGIN_PATH . '/woocommerce_wpml.class.php';

define('WCML_PLUGIN_URL', wpml_filter_include_url( untrailingslashit( plugin_dir_url( __FILE__ ) ) ));

function wpml_wcml_startup() {
    global $woocommerce_wpml;

$woocommerce_wpml = new woocommerce_wpml();
}

if ( defined( 'ICL_SITEPRESS_VERSION' ) && version_compare( ICL_SITEPRESS_VERSION, '3.2', '>=' ) ) {
    //@since WPML 3.2 using dependencies hook
    add_action( 'wpml_loaded', 'wpml_wcml_startup' );
} else {
    //@since 3.3.2 Create instance of WPML_String_Translation using a late 'plugins_loaded' action
    add_action('plugins_loaded', 'wpml_wcml_startup', 10000);
}
