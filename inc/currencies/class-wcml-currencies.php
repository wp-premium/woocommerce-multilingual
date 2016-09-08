<?php

class WCML_Currencies{

    private $woocommerce_wpml;

    public function __construct( &$woocommerce_wpml ) {
        $this->woocommerce_wpml =& $woocommerce_wpml;

        add_action('widgets_init', array($this, 'register_currency_switcher_widget'));

    }

    public function register_currency_switcher_widget(){

        if( $this->woocommerce_wpml->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT ){
            register_widget( 'WCML_Currency_Switcher_Widget' );
        }

    }

}