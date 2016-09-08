<?php

class WCML_Multi_Currency_Install{
    static $multi_currency;
    static $woocommerce_wpml;

    public static function set_up( &$multi_currency, &$woocommerce_wpml ){

        self::$multi_currency   =& $multi_currency;
        self::$woocommerce_wpml =& $woocommerce_wpml;

        if(empty(self::$woocommerce_wpml->settings['multi_currency']['set_up'])){
            self::$woocommerce_wpml->settings['multi_currency']['set_up'] = 1;
            self::$woocommerce_wpml->update_settings();

            self::set_default_currencies_languages();
        }

        return;
    }

    public static function set_default_currencies_languages( $old_value = false, $new_value = false ){
        global $sitepress;

        $settings = self::$woocommerce_wpml->get_settings();
        $active_languages = $sitepress->get_active_languages();
        $wc_currency = $new_value ? $new_value : get_option('woocommerce_currency');

        if( $old_value != $new_value ) {
            $settings = WCML_Multi_Currency_Configuration::currency_options_update_default_currency( $settings, $old_value, $new_value );
        }

        foreach( self::$multi_currency->get_currency_codes() as $code) {
            if( $code == $old_value ) continue;
            foreach($active_languages as $language){
                if(!isset($settings['currency_options'][$code]['languages'][$language['code']])){
                    $settings['currency_options'][$code]['languages'][$language['code']] = 1;
                }
            }
        }

        foreach($active_languages as $language){
            if(!isset($settings['default_currencies'][$language['code']])){
                $settings['default_currencies'][$language['code']] = false;
            }

            if(!isset($settings['currency_options'][$wc_currency]['languages'][$language['code']])){
                $settings['currency_options'][$wc_currency]['languages'][$language['code']] = 1;
            }
        }

        self::$woocommerce_wpml->update_settings( $settings );

    }

}