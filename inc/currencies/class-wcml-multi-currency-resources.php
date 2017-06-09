<?php

class WCML_Multi_Currency_Resources{

    static $multi_currency;
    static $woocommerce_wpml;

    public static function set_up( &$multi_currency, &$woocommerce_wpml ){
        global $pagenow;

        self::$multi_currency =& $multi_currency;
        self::$woocommerce_wpml =& $woocommerce_wpml;

        if(!is_admin() && $pagenow != 'wp-login.php' ){
            self::load_inline_js();
        }

    }

    private static function load_inline_js(){

        wp_register_script('wcml-mc-scripts', WCML_PLUGIN_URL . '/res/js/wcml-multi-currency' . WCML_JS_MIN . '.js', array('jquery'), WCML_VERSION, true);

        wp_enqueue_script('wcml-mc-scripts');

        $script_vars['wcml_spinner']    = WCML_PLUGIN_URL . '/res/images/ajax-loader.gif';
        $script_vars['current_currency']= array(
            'code'  => self::$multi_currency->get_client_currency(),
            'symbol'=> get_woocommerce_currency_symbol( self::$multi_currency->get_client_currency() )
        );

	    if( !empty(self::$multi_currency->W3TC) || function_exists('wp_cache_is_enabled') && wp_cache_is_enabled() ){
		    $script_vars['w3tc'] = 1;
	    }

        wp_localize_script('wcml-mc-scripts', 'wcml_mc_settings', $script_vars );

    }

}