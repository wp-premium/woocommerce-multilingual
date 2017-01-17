<?php

class WCML_Multi_Currency_Resources{

    static $multi_currency;

    public static function set_up( &$multi_currency ){

        self::$multi_currency =& $multi_currency;

        if(!is_admin()){
            self::load_inline_js();
        }

        $is_multi_currency = is_admin() && isset ($_GET['page'] ) && $_GET['page'] == 'wpml-wcml'
                             && isset( $_GET['tab'] ) && $_GET['tab'] == 'multi-currency';

        if( !is_admin() || $is_multi_currency ){
            self::register_css();
        }

    }

    private static function load_inline_js(){
        global $woocommerce_wpml;

        wp_register_script('wcml-mc-scripts', WCML_PLUGIN_URL . '/res/js/wcml-multi-currency' . WCML_JS_MIN . '.js', array('jquery'), WCML_VERSION, true);

        wp_enqueue_script('wcml-mc-scripts');

        $script_vars['wcml_mc_nonce']   = wp_create_nonce( 'switch_currency' );
        $script_vars['wcml_spinner']    = WCML_PLUGIN_URL . '/res/images/ajax-loader.gif';
        $script_vars['current_currency']= array(
            'code'  => self::$multi_currency->get_client_currency(),
            'symbol'=> get_woocommerce_currency_symbol( self::$multi_currency->get_client_currency() )
        );

        if( !empty(self::$multi_currency->W3TC) ){
            $script_vars['w3tc'] = 1;
        }

        wp_localize_script('wcml-mc-scripts', 'wcml_mc_settings', $script_vars );

    }

    private static function register_css(){
        wp_register_style( 'currency-switcher', WCML_PLUGIN_URL . '/res/css/currency-switcher.css', null, WCML_VERSION );
        wp_enqueue_style('currency-switcher');
    }


}